const TOAST_CONTAINER_ID = 'dg-toast-container'
const TOAST_STYLE_ID = 'dg-toast-styles'

function ensureStyles() {
  if (document.getElementById(TOAST_STYLE_ID)) {
    return
  }

  const style = document.createElement('style')
  style.id = TOAST_STYLE_ID
  style.innerHTML = `
    #${TOAST_CONTAINER_ID} {
      position: fixed;
      top: 16px;
      right: 16px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      z-index: 9999;
      pointer-events: none;
    }

    .dg-toast {
      min-width: 260px;
      max-width: 360px;
      padding: 12px 14px;
      border-radius: 10px;
      color: #0b1120;
      background: #fff;
      box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1), 0 1px 4px rgba(15, 23, 42, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
      border-left: 5px solid #0ea5e9;
      opacity: 0;
      transform: translateY(-6px);
      transition: opacity 160ms ease, transform 160ms ease;
      pointer-events: auto;
      font-size: 14px;
      line-height: 1.4;
    }

    .dg-toast-show {
      opacity: 1;
      transform: translateY(0);
    }

    .dg-toast-icon {
      font-size: 16px;
    }

    .dg-toast-success {
      border-left-color: #16a34a;
    }

    .dg-toast-error {
      border-left-color: #dc2626;
    }

    .dg-toast-warning {
      border-left-color: #d97706;
    }
  `

  document.head.appendChild(style)
}

function ensureContainer() {
  let container = document.getElementById(TOAST_CONTAINER_ID)

  if (!container) {
    container = document.createElement('div')
    container.id = TOAST_CONTAINER_ID
    document.body.appendChild(container)
  }

  return container
}

function createToast(type, message, icon) {
  const toast = document.createElement('div')
  toast.className = `dg-toast dg-toast-${type}`

  const iconSpan = document.createElement('span')
  iconSpan.className = 'dg-toast-icon'
  iconSpan.textContent = icon

  const textSpan = document.createElement('span')
  textSpan.textContent = message

  toast.appendChild(iconSpan)
  toast.appendChild(textSpan)

  return toast
}

function showToast(type, message) {
  if (!message) return

  ensureStyles()
  const container = ensureContainer()
  const iconMap = {
    success: '✓',
    error: '⚠',
    warning: '!',
  }

  const toast = createToast(type, message, iconMap[type] || 'ℹ')
  container.appendChild(toast)

  requestAnimationFrame(() => {
    toast.classList.add('dg-toast-show')
  })

  setTimeout(() => {
    toast.classList.remove('dg-toast-show')
    setTimeout(() => {
      toast.remove()
      if (!container.hasChildNodes()) {
        container.remove()
      }
    }, 180)
  }, 3400)
}

export function showSuccess(message) {
  showToast('success', message)
}

export function showError(message) {
  showToast('error', message)
}

export function showWarning(message) {
  showToast('warning', message)
}
