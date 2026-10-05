// Тонкая обёртка над REST API приложения Main.
// Запросы идут на тот же origin: nginx проксирует /api/ в PHP-FPM.

const API_BASE = '/api/v1/main'

/**
 * Выполнить GET-запрос к API и вернуть разобранный JSON.
 * @param {string} path путь относительно /api/v1/main
 * @returns {Promise<any>}
 */
async function get(path) {
  const response = await fetch(`${API_BASE}${path}`, {
    method: 'GET',
    headers: { Accept: 'application/json' },
  })

  if (!response.ok) {
    throw new Error(`API request failed: ${response.status}`)
  }

  return response.json()
}

/**
 * Получить список тем раздела «Помощь».
 * @returns {Promise<Array<{ id: number, title: string, description: string, created_at: string }>>}
 */
export async function fetchFaq() {
  const data = await get('/faq')

  return Array.isArray(data?.items) ? data.items : []
}
