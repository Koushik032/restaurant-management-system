import api from "@/services/api";

const backendUrl = () => {
  const baseUrl =
    api.defaults.baseURL ||
    import.meta.env.VITE_API_URL ||
    "";

  return baseUrl
    .replace(/\/api\/?$/, "")
    .replace(/\/$/, "");
};

export const resolveMediaUrl = (
  path
) => {
  if (!path) {
    return null;
  }

  const value = String(path);

  if (
    value.startsWith("http://") ||
    value.startsWith("https://") ||
    value.startsWith("blob:") ||
    value.startsWith("data:")
  ) {
    return value;
  }

  const cleanPath =
    value.startsWith("/")
      ? value
      : `/${value}`;

  return `${backendUrl()}${cleanPath}`;
};