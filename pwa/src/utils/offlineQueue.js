const STORAGE_KEY = 'dg_offline_punches'

function readQueue() {
  if (typeof localStorage === 'undefined') return []

  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored ? JSON.parse(stored) : []
  } catch (error) {
    console.error('Impossibile leggere la coda offline:', error)
    return []
  }
}

function writeQueue(queue) {
  if (typeof localStorage === 'undefined') return

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(queue))
  } catch (error) {
    console.error('Impossibile salvare la coda offline:', error)
  }
}

export function getQueuedPunches() {
  return readQueue()
}

export function addPunchToQueue(punchPayload) {
  const queue = readQueue()
  const record = {
    id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
    ...punchPayload,
  }

  queue.push(record)
  writeQueue(queue)

  return record
}

export function clearPunchFromQueue(id) {
  const queue = readQueue().filter((item) => item.id !== id)
  writeQueue(queue)

  return queue
}
