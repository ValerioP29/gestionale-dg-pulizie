export async function tryPosition(options = {}) {
  if (!navigator?.geolocation) {
    throw new Error('NO_POSITION')
  }

  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude, accuracy } = position.coords
        resolve({ latitude, longitude, accuracy })
      },
      (error) => {
        reject(error)
      },
      options
    )
  })
}

export async function getStablePosition() {
  try {
    return await tryPosition({ enableHighAccuracy: true, timeout: 8000 })
  } catch (firstError) {
    if (firstError?.code === 1) {
      throw firstError
    }

    try {
      return await tryPosition({ enableHighAccuracy: false, timeout: 5000 })
    } catch (secondError) {
      if (secondError?.code === 1) {
        throw secondError
      }

      throw new Error('NO_POSITION')
    }
  }
}
