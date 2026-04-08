const ID_TOKEN_KEY = "api_token" as string;

/**
 * @description get token form localStorage
 */
export const getToken = (): string | null => {
  return window.localStorage.getItem(ID_TOKEN_KEY)
    || window.localStorage.getItem("api_token")  // fallback untuk sesi lama
    || null;
};

/**
 * @description save token into localStorage
 */
export const saveToken = (token: string): void => {
  window.localStorage.setItem(ID_TOKEN_KEY, token);
};

/**
 * @description remove token form localStorage
 */
export const destroyToken = (): void => {
  window.localStorage.removeItem(ID_TOKEN_KEY);
  window.localStorage.removeItem("api_token"); // hapus sesi lama juga
};

export default { getToken, saveToken, destroyToken };
