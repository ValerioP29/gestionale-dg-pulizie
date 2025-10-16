import axios from "axios";

axios.defaults.withCredentials = true;
axios.defaults.baseURL = "http://127.0.0.1:8000"; // o in prod: https://api.dgpulizie.it

export async function login(email, password) {
  // 1️⃣ prepara il CSRF
  await axios.get("/sanctum/csrf-cookie");

  // 2️⃣ poi fai login
  const res = await axios.post("/login", { email, password });
  return res.data.user;
}

export async function logout() {
  await axios.post("/logout");
}

export async function getUser() {
  const res = await axios.get("/api/me");
  return res.data;
}
