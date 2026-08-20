const DEFAULT_API_BASE_URL =
  "http://127.0.0.1:8000/api";

export const getApiOrigin = () => {
  const apiBase = String(
    import.meta.env.VITE_API_BASE_URL ||
      DEFAULT_API_BASE_URL
  ).trim();

  if (!apiBase) {
    return "";
  }

  try {
    const parsedUrl = new URL(apiBase);

    return `${parsedUrl.protocol}//${parsedUrl.host}`;
  } catch {
    if (
      typeof window !== "undefined" &&
      apiBase.startsWith("/")
    ) {
      return window.location.origin;
    }

    return apiBase
      .replace(/\/api\/?$/i, "")
      .replace(/\/+$/, "");
  }
};

const normalizeStoragePath = (value) => {
  return String(value || "")
    .trim()
    .replace(/\\/g, "/")
    .replace(/^\/+/, "")
    .replace(/^public\//i, "")
    .replace(/^storage\//i, "");
};

export const resolveMediaUrl = (value) => {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return "";
  }

  const rawValue = String(value).trim();

  if (!rawValue) {
    return "";
  }

  if (
    rawValue.startsWith("blob:") ||
    rawValue.startsWith("data:")
  ) {
    return rawValue;
  }

  const apiOrigin = getApiOrigin();

  /*
   * Absolute URL
   */
  if (
    /^https?:\/\//i.test(rawValue)
  ) {
    try {
      const parsedUrl =
        new URL(rawValue);

      const storagePosition =
        parsedUrl.pathname.indexOf(
          "/storage/"
        );

      /*
       * Laravel storage image.
       *
       * Backend APP_URL wrong হলেও
       * current API origin ব্যবহার করবে।
       */
      if (storagePosition !== -1) {
        const storagePath =
          parsedUrl.pathname.slice(
            storagePosition
          );

        return (
          `${apiOrigin}${storagePath}` +
          `${parsedUrl.search}` +
          `${parsedUrl.hash}`
        );
      }

      /*
       * External URL
       */
      return rawValue;
    } catch {
      return rawValue;
    }
  }

  /*
   * /storage/menu-items/file.jpg
   */
  if (
    rawValue.startsWith("/storage/")
  ) {
    return `${apiOrigin}${rawValue}`;
  }

  /*
   * storage/menu-items/file.jpg
   */
  if (
    rawValue.startsWith("storage/")
  ) {
    return `${apiOrigin}/${rawValue}`;
  }

  /*
   * menu-items/file.jpg
   */
  const storagePath =
    normalizeStoragePath(rawValue);

  if (!storagePath) {
    return "";
  }

  return `${apiOrigin}/storage/${storagePath}`;
};