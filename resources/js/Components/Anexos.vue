<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useConfirm } from 'primevue/useconfirm';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Anexo } from '@/types';

/**
 * Anexos e comprovantes (regra 3.8).
 *
 * Componente único, compartilhado por pagamento, reembolso e contrato — a tabela
 * de anexos é polimórfica e o controller resolve o dono pela URL.
 *
 * O download nunca é link direto para o arquivo: comprovante carrega dado
 * bancário, então o arquivo mora em disco privado e a rota autenticada é a
 * única porta.
 */
const props = withDefaults(
    defineProps<{
        tipoRegistro: 'pagamento' | 'reembolso' | 'contrato';
        registroId: number;
        anexos: Anexo[];
        /** Falso em registro fechado (pago, cancelado): mostra sem deixar mexer. */
        podeGerenciar?: boolean;
        titulo?: string;
        /** Texto do estado vazio, que muda conforme a tela. */
        vazio?: string;
        /**
         * Destaca o anexo mais recente. O reembolso usa: quem aprova precisa ver
         * o comprovante antes de decidir, não caçá-lo numa lista.
         */
        destacarPrimeiro?: boolean;
    }>(),
    {
        podeGerenciar: true,
        titulo: 'Anexos',
        vazio: 'Nenhum anexo enviado.',
        destacarPrimeiro: false,
    },
);

const { formatarDataHora } = useFormato();
const confirm = useConfirm();

const EXTENSOES = '.pdf,.jpg,.jpeg,.png,.xml';
const TAMANHO_MAXIMO_MB = 10;

const arrastando = ref(false);
const campoArquivo = ref<HTMLInputElement | null>(null);

const form = useForm({ arquivo: null as File | null });

const destaque = computed<Anexo | null>(() =>
    props.destacarPrimeiro ? (props.anexos[0] ?? null) : null,
);

const demais = computed(() => (props.destacarPrimeiro ? props.anexos.slice(1) : props.anexos));

const formatarTamanho = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;

    const kb = bytes / 1024;

    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`;
};

const limparCampo = () => {
    // Sem limpar, reescolher o mesmo arquivo não dispara change de novo.
    if (campoArquivo.value) {
        campoArquivo.value.value = '';
    }
};

const enviar = (arquivo: File | null | undefined) => {
    if (!arquivo) return;

    form.arquivo = arquivo;
    form.post(
        route('anexos.store', {
            tipoRegistro: props.tipoRegistro,
            registroId: props.registroId,
        }),
        {
            preserveScroll: true,
            onFinish: () => {
                form.reset();
                limparCampo();
            },
        },
    );
};

const aoSoltar = (evento: DragEvent) => {
    arrastando.value = false;
    enviar(evento.dataTransfer?.files?.[0]);
};

const remover = (anexo: Anexo) => {
    confirm.require({
        header: 'Remover anexo',
        message: `"${anexo.nome_arquivo}" será apagado do servidor e não poderá ser recuperado. A remoção fica registrada na auditoria.`,
        acceptLabel: 'Remover',
        rejectLabel: 'Voltar',
        acceptProps: { severity: 'danger', size: 'small' },
        rejectProps: { severity: 'secondary', text: true, size: 'small' },
        accept: () =>
            router.delete(route('anexos.destroy', anexo.id), { preserveScroll: true }),
    });
};
</script>

<template>
    <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
        <h2 class="mb-3.5 text-[12px] font-semibold uppercase tracking-[0.06em] text-ink-55">
            {{ titulo }}
        </h2>

        <!-- Anexo em destaque -->
        <div
            v-if="destaque"
            class="mb-3 flex flex-wrap items-center justify-between gap-4 rounded-md border border-azul-100 bg-azul-50 px-4 py-4"
        >
            <div class="flex min-w-0 items-center gap-3.5">
                <span
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-azul-100 bg-white text-corebanx-azul"
                >
                    <Icone nome="paperclip" :tamanho="20" />
                </span>
                <div class="min-w-0">
                    <p class="truncate text-[13.75px] font-semibold">
                        {{ destaque.nome_arquivo }}
                    </p>
                    <p class="mt-0.5 text-[12px] text-ink-55">
                        <span class="mono">{{ formatarTamanho(destaque.tamanho) }}</span>
                        · Enviado por {{ destaque.enviado_por?.name ?? 'sistema' }} ·
                        <span class="mono">{{ formatarDataHora(destaque.created_at) }}</span>
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1.5">
                <a :href="route('anexos.download', destaque.id)">
                    <Button label="Baixar" severity="secondary" size="small">
                        <template #icon><Icone nome="download" :tamanho="16" /></template>
                    </Button>
                </a>
                <button
                    v-if="podeGerenciar"
                    type="button"
                    class="rounded-lg p-2 text-ink-55 hover:bg-white hover:text-perigo"
                    :title="`Remover ${destaque.nome_arquivo}`"
                    @click="remover(destaque)"
                >
                    <Icone nome="trash" :tamanho="15" />
                </button>
            </div>
        </div>

        <!-- Lista -->
        <div
            v-for="anexo in demais"
            :key="anexo.id"
            class="mb-2.5 flex items-center justify-between gap-2 rounded-md border border-ink-8 px-3.5 py-3"
        >
            <div class="flex min-w-0 items-center gap-2.5">
                <Icone nome="paperclip" :tamanho="18" class="shrink-0 text-ink-55" />
                <div class="min-w-0">
                    <p class="truncate text-[13px] font-semibold">{{ anexo.nome_arquivo }}</p>
                    <p class="text-[12px] text-ink-55">
                        <span class="mono">{{ formatarTamanho(anexo.tamanho) }}</span>
                        · Enviado por {{ anexo.enviado_por?.name ?? 'sistema' }} ·
                        <span class="mono">{{ formatarDataHora(anexo.created_at) }}</span>
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-0.5">
                <a
                    :href="route('anexos.download', anexo.id)"
                    class="rounded-lg p-2 text-ink-70 hover:bg-ink-8"
                    :title="`Baixar ${anexo.nome_arquivo}`"
                >
                    <Icone nome="download" :tamanho="15" />
                </a>
                <button
                    v-if="podeGerenciar"
                    type="button"
                    class="rounded-lg p-2 text-ink-55 hover:bg-ink-8 hover:text-perigo"
                    :title="`Remover ${anexo.nome_arquivo}`"
                    @click="remover(anexo)"
                >
                    <Icone nome="trash" :tamanho="15" />
                </button>
            </div>
        </div>

        <!-- Estado vazio -->
        <div
            v-if="!anexos.length"
            class="rounded-md border border-dashed border-ink-16 px-4 py-6 text-center"
        >
            <Icone nome="paperclip" :tamanho="20" class="mx-auto mb-2 text-ink-35" />
            <p class="text-[12.75px] text-ink-55">{{ vazio }}</p>
        </div>

        <!-- Upload -->
        <template v-if="podeGerenciar">
            <input
                :id="`anexo-${tipoRegistro}-${registroId}`"
                ref="campoArquivo"
                type="file"
                class="sr-only"
                :accept="EXTENSOES"
                @change="enviar(($event.target as HTMLInputElement).files?.[0])"
            />

            <label
                :for="`anexo-${tipoRegistro}-${registroId}`"
                class="mt-3 flex cursor-pointer items-center justify-center gap-2 rounded-md border border-dashed px-3.5 py-[11px] text-[12.75px] transition-colors"
                :class="[
                    arrastando
                        ? 'border-laranja-500 bg-laranja-50 text-laranja-700'
                        : 'border-ink-16 text-ink-55 hover:border-ink-35 hover:text-ink-70',
                    form.processing ? 'pointer-events-none opacity-60' : '',
                ]"
                @dragover.prevent="arrastando = true"
                @dragleave.prevent="arrastando = false"
                @drop.prevent="aoSoltar"
            >
                <Icone :nome="form.processing ? 'refresh' : 'upload'" :tamanho="15" />
                {{ form.processing ? 'Enviando…' : 'Arraste ou clique para anexar' }}
            </label>

            <p class="mt-1.5 text-[11.5px] text-ink-55">
                pdf, jpg, jpeg, png ou xml · máx. {{ TAMANHO_MAXIMO_MB }} MB · o download passa
                por rota autenticada, nunca por link direto
            </p>

            <Message
                v-if="form.errors.arquivo"
                severity="error"
                size="small"
                variant="simple"
                class="mt-1.5"
            >
                {{ form.errors.arquivo }}
            </Message>
        </template>
    </div>
</template>
