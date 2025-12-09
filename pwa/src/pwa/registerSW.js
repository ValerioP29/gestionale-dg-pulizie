import { registerSW as register } from 'virtual:pwa-register'

export function registerPWA() {
  register({ immediate: true })
};