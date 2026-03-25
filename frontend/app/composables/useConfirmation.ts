export function useConfirmation<T = unknown>() {
  const itemToDelete = ref<T | null>(null)
  const isDeleting = ref(false)

  function confirm(item: T) {
    itemToDelete.value = item
  }

  function cancel() {
    itemToDelete.value = null
    isDeleting.value = false
  }

  return { itemToDelete, isDeleting, confirm, cancel }
}
