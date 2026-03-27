<script setup lang="ts">
const { certificado, uploadCertificado, removerCertificado } = useEmitente()

const uploading = ref(false)
const fileInput = useTemplateRef('fileInput')
const senha = ref('')
const certFile = ref<File | null>(null)

const certStatus = computed(() => {
  if (!certificado.value?.tem_certificado) {
    return { color: 'neutral' as const, label: 'Sem certificado' }
  }
  if (certificado.value?.info?.expirado) {
    return { color: 'error' as const, label: 'Expirado' }
  }
  const validade = certificado.value?.validade
  if (!validade) return { color: 'neutral' as const, label: 'Sem certificado' }

  const diffDays = Math.floor((new Date(validade).getTime() - Date.now()) / (1000 * 60 * 60 * 24))
  if (diffDays <= 30) {
    return { color: 'warning' as const, label: `Vence em ${diffDays} dia(s)` }
  }
  return { color: 'success' as const, label: 'Válido' }
})

function onFileChange(event: Event) {
  const target = event.target as HTMLInputElement
  certFile.value = target.files?.[0] ?? null
}

async function onUpload() {
  if (!certFile.value || !senha.value) return
  uploading.value = true
  try {
    await uploadCertificado(certFile.value, senha.value)
    certFile.value = null
    senha.value = ''
    if (fileInput.value) fileInput.value.value = ''
  } catch {
    // user gets toast from composable
  } finally {
    uploading.value = false
  }
}

async function onRemove() {
  await removerCertificado()
}

const removing = ref(false)
async function confirmRemove() {
  removing.value = true
  try {
    await onRemove()
  } catch {
    // user gets toast from composable
  } finally {
    removing.value = false
  }
}
</script>

<template>
  <div v-if="!certificado" class="flex items-center justify-center h-48">
    <UIcon name="i-lucide-loader-2" class="animate-spin size-8 text-muted" />
  </div>

  <div v-else class="space-y-6">
    <UPageCard title="Certificado Digital" variant="subtle">
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold">
              Status do Certificado
            </h3>
            <UBadge :color="certStatus.color" variant="subtle" class="mt-1">
              {{ certStatus.label }}
            </UBadge>
          </div>
        </div>

        <template v-if="certificado?.info?.erro">
          <UAlert
            color="error"
            icon="i-lucide-alert-circle"
            :title="certificado.info.erro"
          />
        </template>

        <template v-else-if="certificado?.tem_certificado && certificado?.info">
          <USeparator />
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-muted">CNPJ</span>
              <p>{{ certificado.info.cnpj }}</p>
            </div>
            <div>
              <span class="text-muted">Razão Social</span>
              <p>{{ certificado.info.razao_social }}</p>
            </div>
            <div>
              <span class="text-muted">Válido de</span>
              <p>{{ certificado.info.valido_de }}</p>
            </div>
            <div>
              <span class="text-muted">Válido até</span>
              <p>{{ certificado.info.valido_ate }}</p>
            </div>
          </div>
        </template>

        <template v-else>
          <p class="text-sm text-muted">
            Nenhum certificado digital configurado. Faça o upload do arquivo PFX.
          </p>
        </template>
      </div>
    </UPageCard>

    <UPageCard title="Upload de Certificado" variant="subtle">
      <div class="space-y-4">
        <UFormField label="Arquivo PFX" required>
          <input
            ref="fileInput"
            type="file"
            accept=".pfx,.p12"
            class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
            @change="onFileChange"
          >
        </UFormField>

        <UFormField label="Senha do Certificado" required>
          <UInput
            v-model="senha"
            type="password"
            placeholder="Senha do arquivo PFX"
          />
        </UFormField>

        <div class="flex gap-3">
          <UButton
            label="Enviar Certificado"
            :loading="uploading"
            :disabled="!certFile || !senha"
            @click="onUpload"
          />
          <UButton
            v-if="certificado?.tem_certificado"
            label="Remover Certificado"
            color="error"
            variant="outline"
            :loading="removing"
            @click="confirmRemove"
          />
        </div>
      </div>
    </UPageCard>
  </div>
</template>
