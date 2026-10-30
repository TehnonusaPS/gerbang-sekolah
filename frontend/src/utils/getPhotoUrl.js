import api from '@/services/api';

export const getPhotoUrl = (filename) => {
  if (!filename || typeof filename !== 'string' || filename.trim() === '') {
    return null;
  }

  const cleanFilename = filename.trim();
  const apiBase = api?.defaults?.baseURL || import.meta.env.VITE_API_BASE_URL || '';
  const backendBase = apiBase.replace(/\/api\/?$/, '');
  // Prioritaskan URL Supabase Storage; fallback ke backend lokal
  const supabaseUrl = (
    import.meta.env.VITE_SUPABASE_URL ||
    'https://lfjcgqrjerzzijgazhjl.supabase.co'
  ).replace(/\/$/, '');
  const storageBase = supabaseUrl
    ? `${supabaseUrl}/storage/v1/object/public/uploads`
    : `${backendBase}/storage`;

  // Tangani URL lengkap (http://, https://, blob:, atau data:)
  if (/^(blob:|data:)/i.test(cleanFilename)) {
    return cleanFilename;
  }

  if (/^https?:\/\//i.test(cleanFilename)) {
    try {
      const parsed = new URL(cleanFilename);
      const backendHostname = backendBase ? new URL(backendBase, window?.location?.origin || 'http://127.0.0.1:8000').hostname : '';
      const isLocalhost = (host) => host === 'localhost' || host === '127.0.0.1';

      // Cek apakah URL berasal dari backend (host sama, atau sesama host lokal saat development)
      const isBackendHost =
        (backendHostname && parsed.hostname === backendHostname) ||
        (isLocalhost(backendHostname) && isLocalhost(parsed.hostname));

      const isAppStoragePath = /^\/?storage\//i.test(parsed.pathname);

      // Normalisasi jika merupakan URL aset dari backend lokal
      if (isBackendHost && isAppStoragePath && supabaseUrl) {
        const cleanPath = parsed.pathname.replace(/^\/?storage\//i, '');
        return `${storageBase}/${cleanPath}`;
      }

      // Pertahankan URL eksternal valid lainnya (termasuk Supabase Storage langsung)
      return cleanFilename;
    } catch {
      return cleanFilename;
    }
  }

  // Tangani path relatif dari storage backend
  const cleanPath = cleanFilename.replace(/^\/?(storage\/)?/, '');
  return `${storageBase}/${cleanPath}`;
};
