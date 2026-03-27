<script setup lang="ts">
const { emitente, updateAmbiente } = useEmitente()

const loading = ref(false)
const ambiente = ref<'homologacao' | 'producao'>('homologacao')

watch(emitente, (e) => {
  if (e) {
    ambiente.value = e.ambiente
  }
}, { immediate: true })

async function onChangeAmbiente(value: 'homologacao' | 'producao') {
  loading.value = true
  try {
    await updateAmbiente(value)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div v-if="!emitente" class="flex items-center justify-center h-48">
    <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
  </div>

  <div v-else class="space-y-6">
    <UPageCard title="Ambiente de Emissão" variant="subtle">
      <p class="text-sm text-muted mb-6">
        Defina o ambiente em que os documentos fiscais serão emitidos. O ambiente de homologação é para testes e o de produção para emissões reais.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
          :class="[
            'relative rounded-lg border-2 p-6 text-left transition-all cursor-pointer',
            ambiente === 'homologacao'
              ? 'border-primary bg-primary/5'
              : 'border-default hover:border-primary/50'
          ]"
          :disabled="loading"
          @click="onChangeAmbiente('homologacao')"
        >
          <div class="flex items-center gap-3 mb-3">
            <UIcon name="i-lucide-flask-conical" class="size-6 text-amber-500" />
            <span class="font-semibold text-lg">Homologação</span>
          </div>
          <p class="text-sm text-muted">
            Ambiente de testes. Documentos emitidos não possuem valor fiscal. Utilizado para validação e homologação do sistema.
          </p>
          <div
            v-if="ambiente === 'homologacao'"
            class="absolute top-3 right-3"
          >
            <UIcon name="i-lucide-check-circle-2" class="size-5 text-primary" />
          </div>
        </button>

        <button
          :class="[
            'relative rounded-lg border-2 p-6 text-left transition-all cursor-pointer',
            ambiente === 'producao'
              ? 'border-primary bg-primary/5'
              : 'border-default hover:border-primary/50'
          ]"
          :disabled="loading"
          @click="onChangeAmbiente('producao')"
        >
          <div class="flex items-center gap-3 mb-3">
            <UIcon name="i-lucide-rocket" class="size-6 text-green-500" />
            <span class="font-semibold text-lg">Produção</span>
          </div>
          <p class="text-sm text-muted">
            Ambiente real. Documentos emitidos possuem valor fiscal e serão transmitidos à SEFAZ.
          </p>
          <div
            v-if="ambiente === 'producao'"
            class="absolute top-3 right-3"
          >
            <UIcon name="i-lucide-check-circle-2" class="size-5 text-primary" />
          </div>
        </button>
      </div>
    </UPageCard>

    <UAlert
      v-if="ambiente === 'producao'"
      color="warning"
      icon="i-lucide-alert-triangle"
      title="Atenção"
      description="Ao utilizar o ambiente de produção, todos os documentos emitidos terão valor fiscal. Certifique-se de que o certificado digital e os dados estão corretos."
    />
  </div>
</template>
