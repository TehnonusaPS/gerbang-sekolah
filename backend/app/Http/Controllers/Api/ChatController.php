<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get list of eligible contacts for the logged-in user.
     * Contacts are filtered by sharing the same school_id or foundation_id.
     */
    /**
     * Get list of eligible contacts for the logged-in user.
     * Contacts are filtered by sharing the same school_id or foundation_id.
     */
    public function getContacts(Request $request): JsonResponse
    {
        $user = $request->user();
        $eligibleIds = $this->getEligibleContactIds($user);

        $contacts = User::with('role')
            ->whereIn('id', $eligibleIds)
            ->get();

        // Map and count unread messages from each contact
        $mappedContacts = $contacts->map(function ($contact) use ($user) {
            $unreadCount = Message::where('sender_id', $contact->id)
                ->where('receiver_id', $user->id)
                ->where('is_read', false)
                ->count();

            // Get last message between them
            $lastMessage = Message::where(function ($q) use ($user, $contact) {
                $q->where('sender_id', $user->id)->where('receiver_id', $contact->id);
            })->orWhere(function ($q) use ($user, $contact) {
                $q->where('sender_id', $contact->id)->where('receiver_id', $user->id);
            })->latest()->first();

            return [
                'id' => $contact->id,
                'name' => $contact->name,
                'email' => $contact->email,
                'role' => $contact->role ? $contact->role->name : 'guest',
                'roleLabel' => $contact->role ? $contact->role->label : 'Guest',
                'photo' => $contact->photo,
                'unread' => $unreadCount,
                'preview' => $lastMessage ? $lastMessage->message : '',
                'time' => $lastMessage ? $lastMessage->created_at->format('H:i') : '',
                'last_message_at' => $lastMessage ? $lastMessage->created_at->toISOString() : null,
            ];
        })->sortByDesc('last_message_at')->values();

        return response()->json([
            'contacts' => $mappedContacts
        ]);
    }

    /**
     * Get chat history with a specific recipient.
     */
    public function getMessages(Request $request, $recipientId): JsonResponse
    {
        $user = $request->user();
        $eligibleIds = $this->getEligibleContactIds($user);

        if (!in_array($recipientId, $eligibleIds)) {
            return response()->json([
                'message' => 'Anda tidak diizinkan mengakses percakapan ini.'
            ], 403);
        }

        $userId = $user->id;

        // Fetch thread
        $messages = Message::where(function ($q) use ($userId, $recipientId) {
            $q->where('sender_id', $userId)->where('receiver_id', $recipientId);
        })->orWhere(function ($q) use ($userId, $recipientId) {
            $q->where('sender_id', $recipientId)->where('receiver_id', $userId);
        })
        ->orderBy('created_at', 'asc')
        ->get();

        // Mark incoming messages as read
        $affectedRows = Message::where('sender_id', $recipientId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        if ($affectedRows > 0) {
            try {
                broadcast(new \App\Events\MessageRead($userId, (int)$recipientId))->toOthers();
            } catch (\Exception $e) {
                \Log::warning('WebSocket MessageRead broadcast failed: ' . $e->getMessage());
            }
        }

        $mappedMessages = $messages->map(function ($message) use ($userId) {
            return [
                'id' => $message->id,
                'from' => $message->sender_id == $userId ? 'me' : 'other',
                'text' => $message->message,
                'time' => $message->created_at->format('H:i'),
                'status' => $message->is_read ? 'read' : 'sent',
                'created_at' => $message->created_at->toISOString(),
            ];
        });

        return response()->json([
            'messages' => $mappedMessages
        ]);
    }

    /**
     * Send a new message to a recipient.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $sender = $request->user();
        $eligibleIds = $this->getEligibleContactIds($sender);

        // Security check: ensure they are in the list of eligible contacts
        if (!in_array($request->recipient_id, $eligibleIds)) {
            return response()->json([
                'message' => 'Anda tidak diizinkan mengirim pesan ke pengguna di luar sekolah/yayasan Anda.'
            ], 403);
        }

        // Store message
        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $request->recipient_id,
            'message' => $request->message,
            'is_read' => false,
        ]);

        // Broadcast event
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Exception $e) {
            // Log warning but don't crash, message is still saved in DB
            \Log::warning('WebSocket broadcast failed: ' . $e->getMessage());
        }

        // Passive cleanup: 1 out of 20 chance to prune messages older than 60 days (2 months)
        if (rand(1, 20) === 1) {
            try {
                Message::where('created_at', '<', now()->subDays(60))->delete();
            } catch (\Exception $e) {
                \Log::warning('Passive chat cleanup error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'data' => [
                'id' => $message->id,
                'from' => 'me',
                'text' => $message->message,
                'time' => $message->created_at->format('H:i'),
                'status' => 'sent',
                'created_at' => $message->created_at->toISOString(),
            ]
        ], 201);
    }

    /**
     * Get IDs of all eligible contacts for a user.
     */
    private function getEligibleContactIds($user): array
    {
        $user->loadMissing('role', 'parentProfile');

        $allowedRoles = ['guru', 'siswa', 'orang_tua'];
        if (!$user->role || !in_array($user->role->name, $allowedRoles)) {
            return [];
        }

        // 1. Guru: see only Siswa (same school) and Orang Tua (parents of children in same school)
        if ($user->role && $user->role->name === 'guru') {
            $schoolId = $user->school_id;
            if (!$schoolId) {
                return [];
            }
            
            return User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->where(function ($q) use ($schoolId) {
                    // Match Siswa in the same school
                    $q->where(function ($sub) use ($schoolId) {
                        $sub->whereHas('role', function ($r) {
                            $r->where('name', 'siswa');
                        })->where('school_id', $schoolId);
                    });
                    
                    // Match Orang Tua of children in the same school
                    $q->orWhere(function ($sub) use ($schoolId) {
                        $sub->whereHas('role', function ($r) {
                            $r->where('name', 'orang_tua');
                        })->whereHas('parentProfile.children.user', function ($u) use ($schoolId) {
                            $u->where('school_id', $schoolId);
                        });
                    });
                })
                ->pluck('id')
                ->toArray();
        }

        // 2. Siswa: see only Guru (same school) and their own homeroom teacher
        if ($user->role && $user->role->name === 'siswa') {
            $schoolId = $user->school_id;
            if (!$schoolId) {
                return [];
            }
            
            $user->loadMissing('studentProfile');
            $homeroomTeacherId = null;
            if ($user->studentProfile && $user->studentProfile->classroom_id) {
                $homeroomTeacherId = \App\Models\Classroom::where('id', $user->studentProfile->classroom_id)
                    ->value('homeroom_teacher_id');
            }
            
            return User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->where(function ($q) use ($schoolId, $homeroomTeacherId) {
                    // Match Guru in the same school
                    $q->where(function ($sub) use ($schoolId) {
                        $sub->whereHas('role', function ($r) {
                            $r->where('name', 'guru');
                        })->where('school_id', $schoolId);
                    });
                    
                    // Match their own Wali Kelas
                    if ($homeroomTeacherId) {
                        $q->orWhere('id', $homeroomTeacherId);
                    }
                })
                ->pluck('id')
                ->toArray();
        }

        // 3. Orang Tua: see only Guru (in children's schools) and homeroom teachers of their children's classrooms
        if ($user->role && $user->role->name === 'orang_tua') {
            $user->loadMissing('parentProfile.children.user');
            
            $childrenSchoolIds = [];
            $childrenClassroomIds = [];
            
            if ($user->parentProfile) {
                foreach ($user->parentProfile->children as $child) {
                    if ($child->user && $child->user->school_id) {
                        $childrenSchoolIds[] = $child->user->school_id;
                    }
                    if ($child->classroom_id) {
                        $childrenClassroomIds[] = $child->classroom_id;
                    }
                }
            }
            
            $childrenSchoolIds = array_unique(array_filter($childrenSchoolIds));
            $childrenClassroomIds = array_unique(array_filter($childrenClassroomIds));
            
            $homeroomTeacherIds = [];
            if (!empty($childrenClassroomIds)) {
                $homeroomTeacherIds = \App\Models\Classroom::whereIn('id', $childrenClassroomIds)
                    ->pluck('homeroom_teacher_id')
                    ->filter()
                    ->unique()
                    ->toArray();
            }
            
            return User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->where(function ($q) use ($childrenSchoolIds, $homeroomTeacherIds) {
                    // Match Guru in children's schools
                    if (!empty($childrenSchoolIds)) {
                        $q->where(function ($sub) use ($childrenSchoolIds) {
                            $sub->whereHas('role', function ($r) {
                                $r->where('name', 'guru');
                            })->whereIn('school_id', $childrenSchoolIds);
                        });
                    }
                    
                    // Match children's own Wali Kelas
                    if (!empty($homeroomTeacherIds)) {
                        $q->orWhereIn('id', $homeroomTeacherIds);
                    }
                })
                ->pluck('id')
                ->toArray();
        }

        // 4. Default Fallback (e.g. admin_yayasan, other roles)
        $userSchoolIds = [];
        $userFoundationIds = [];

        if ($user->school_id) {
            $userSchoolIds[] = $user->school_id;
            $school = \App\Models\School::find($user->school_id);
            if ($school && $school->foundation_id) {
                $userFoundationIds[] = $school->foundation_id;
            }
        }

        if ($user->foundation_id) {
            $userFoundationIds[] = $user->foundation_id;
            $schoolIds = \App\Models\School::where('foundation_id', $user->foundation_id)->pluck('id')->toArray();
            $userSchoolIds = array_merge($userSchoolIds, $schoolIds);
        }

        $userSchoolIds = array_unique(array_filter($userSchoolIds));
        $userFoundationIds = array_unique(array_filter($userFoundationIds));

        $query = User::where('id', '!=', $user->id)
            ->where('is_active', true);

        $query->where(function ($q) use ($userSchoolIds, $userFoundationIds) {
            if (!empty($userSchoolIds)) {
                $q->whereIn('school_id', $userSchoolIds);
            }
            if (!empty($userFoundationIds)) {
                $q->orWhereIn('foundation_id', $userFoundationIds);
                $q->orWhereHas('school', function ($s) use ($userFoundationIds) {
                    $s->whereIn('foundation_id', $userFoundationIds);
                });
            }
        });

        return $query->pluck('id')->toArray();
    }

    /**
     * Mark a specific message as read.
     */
    public function markAsRead(Request $request, $messageId): JsonResponse
    {
        $userId = $request->user()->id;

        $message = Message::where('id', $messageId)
            ->where('receiver_id', $userId)
            ->first();

        if ($message) {
            $message->update(['is_read' => true]);
            
            try {
                broadcast(new \App\Events\MessageRead($userId, (int)$message->sender_id))->toOthers();
            } catch (\Exception $e) {
                \Log::warning('WebSocket MessageRead broadcast failed in markAsRead: ' . $e->getMessage());
            }

            return response()->json(['message' => 'Pesan ditandai sebagai dibaca.']);
        }

        return response()->json(['message' => 'Pesan tidak ditemukan atau bukan milik Anda.'], 404);
    }

    /**
     * Get total unread messages count for the logged-in user.
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $allowedRoles = ['guru', 'siswa', 'orang_tua'];
        if (!$user->role || !in_array($user->role->name, $allowedRoles)) {
            return response()->json([
                'unread_count' => 0
            ]);
        }

        $userId = $user->id;
        $count = Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count
        ]);
    }
}
