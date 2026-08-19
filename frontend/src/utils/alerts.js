// Helper centralizado para SweetAlert2 con temática RefuGuía

const getSwal = () => {
  return window.Swal || {
    fire: (opts) => alert(opts.title + '\n' + (opts.text || ''))
  }
}

export const showSuccess = (title, text = '', timer = 3500) => {
  return getSwal().fire({
    title,
    html: text,
    icon: 'success',
    background: '#0e1626',
    color: '#ffffff',
    iconColor: '#34d399',
    timer,
    timerProgressBar: true,
    customClass: {
      popup: 'refu-swal-popup',
      title: 'refu-swal-title',
      htmlContainer: 'refu-swal-html',
      confirmButton: 'refu-swal-confirm'
    }
  })
}

export const showError = (title, text = '') => {
  return getSwal().fire({
    title,
    html: text,
    icon: 'error',
    background: '#0e1626',
    color: '#ffffff',
    iconColor: '#ef4444',
    customClass: {
      popup: 'refu-swal-popup',
      title: 'refu-swal-title',
      htmlContainer: 'refu-swal-html',
      confirmButton: 'refu-swal-confirm'
    }
  })
}

export const showWarning = (title, text = '') => {
  return getSwal().fire({
    title,
    html: text,
    icon: 'warning',
    background: '#0e1626',
    color: '#ffffff',
    iconColor: '#fbbf24',
    customClass: {
      popup: 'refu-swal-popup',
      title: 'refu-swal-title',
      htmlContainer: 'refu-swal-html',
      confirmButton: 'refu-swal-confirm'
    }
  })
}

export const showConfirm = async (title, text = '', confirmText = 'Confirmar', cancelText = 'Cancelar') => {
  const result = await getSwal().fire({
    title,
    html: text,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    background: '#0e1626',
    color: '#ffffff',
    iconColor: '#6366f1',
    reverseButtons: true,
    customClass: {
      popup: 'refu-swal-popup',
      title: 'refu-swal-title',
      htmlContainer: 'refu-swal-html',
      confirmButton: 'refu-swal-confirm',
      cancelButton: 'refu-swal-cancel'
    }
  })
  return result.isConfirmed
}

export const showToast = (title, icon = 'success') => {
  const Toast = getSwal().mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: '#0e1626',
    color: '#ffffff',
    customClass: {
      popup: 'refu-swal-popup'
    }
  })
  Toast.fire({ icon, title })
}
