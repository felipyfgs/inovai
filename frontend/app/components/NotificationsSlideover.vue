<script setup lang="ts">
const open = ref(false)
</script>

<template>
  <USlideover v-model:open="open" title="Notificações">
    <div class="p-4 text-center text-muted">
      Nenhuma notificação no momento.
    </div>
  </USlideover>
</template>
