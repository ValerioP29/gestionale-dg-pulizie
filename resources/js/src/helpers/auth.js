import apiClient from "../../services/apiClient";

export async function login(email, password) {
  await apiClient.get("/sanctum/csrf-cookie");

  const res = await apiClient.post("/login", { email, password });
  return res.data?.data ?? null;
}

export async function logout() {
  await apiClient.post("/logout");
}

export async function getUser() {
  const res = await apiClient.get("/api/me");
  return res.data?.data ?? null;
}
