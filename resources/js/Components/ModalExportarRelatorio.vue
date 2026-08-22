<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';

/**
 * Exportação de relatório.
 *
 * A interface existe; o endpoint ainda não. `relatorios.exportar` responde 501
 * até a exportação Excel/PDF entrar no backend, então a ação fica desabilitada
 * em vez de disparar um erro. Quando o endpoint existir, basta trocar o botão
 * desabilitado por um link para route('relatorios.exportar', {...parametros}).
 */
const props = defineProps<{
    visivel: boolean;
    filtros: {
        inicio: string;
        fim: string;
        categoria_id: number | null;
        status: string | null;
    };
    /** Rótulos do que está filtrado, para o resumo do que seria exportado. */
    resumoFiltros: { categoria: string | null; status: string | null };
    quantidade: number;
}>();

const emit = defineEmits<{ 'update:visivel': [valor: boolean] }>();

const { formatarData } = useFormato();

const FORMATOS = [
    { valor: 'xlsx', rotulo: 'Planilha Excel', detalhe: '.xlsx' },
    { valor: 'pdf', rotulo: 'Documento PDF', detalhe: '.pdf' },
];

const formato = ref('xlsx');

watch(
    () => props.visivel,
    (aberto) => {
        if (aberto) {
            formato.value = 'xlsx';
        }
    },
);

const periodo = computed(
    () => `${formatarData(props.filtros.inicio)} a ${formatarData(props.filtros.fim)}`,
);

const fechar = () => emit('update:visivel', false);
</script>

<template>
    <Dialog
        :visible="visivel"
        modal
        :draggable="false"
        class="w-full max-w-[460px]"
        @update:visible="emit('update:visivel', $event)"
    >
        <template #header>
            <div class="min-w-0">
                <h3 class="text-[15.5px] font-semibold">Exportar relatório</h3>
                <p class="mt-[3px] text-[12.25px] text-ink-55">
                    Mantém os filtros aplicados na tela
                </p>
            </div>
        </template>

        <div class="space-y-3.5">
            <!-- O que sairia no arquivo, para ninguém exportar o recorte errado. -->
            <div class="rounded-md border border-ink-8 px-3.5 py-3 text-[12.5px]">
                <p class="mb-1.5 text-[11.5px] uppercase tracking-[0.04em] text-ink-55">
                    O que vai no arquivo
                </p>
                <ul class="space-y-1 text-ink-70">
                    <li>
                        Período: <span class="mono font-semibold">{{ periodo }}</span>
                    </li>
                    <li>
                        Categoria:
                        <span class="font-semibold">{{ resumoFiltros.categoria ?? 'todas' }}</span>
                    </li>
                    <li>
                        Status:
                        <span class="font-semibold">{{ resumoFiltros.status ?? 'todos' }}</span>
                    </li>
                    <li>
                        <span class="mono font-semibold">{{ quantidade }}</span>
                        lançamento{{ quantidade === 1 ? '' : 's' }}
                    </li>
                </ul>
            </div>

            <div class="flex flex-col gap-2" role="radiogroup" aria-label="Formato do arquivo">
                <button
                    v-for="opcao in FORMATOS"
                    :key="opcao.valor"
                    type="button"
                    role="radio"
                    :aria-checked="formato === opcao.valor"
                    class="flex items-center gap-2.5 rounded-[9px] border-[1.5px] bg-white px-3 py-2.5 text-left transition-colors"
                    :class="
                        formato === opcao.valor
                            ? 'border-azul-500 bg-azul-50'
                            : 'border-ink-16 hover:border-azul-300 hover:bg-azul-50'
                    "
                    @click="formato = opcao.valor"
                >
                    <Icone nome="file" :tamanho="17" class="shrink-0 text-ink-55" />
                    <span class="flex-1 text-[13.25px] font-semibold">{{ opcao.rotulo }}</span>
                    <span class="mono text-[11.5px] text-ink-55">{{ opcao.detalhe }}</span>
                </button>
            </div>

            <Aviso tom="atencao" icone="alertTriangle">
                <strong>Exportação em breve.</strong> A geração do arquivo ainda não existe no
                backend — a rota responde 501. Os números da tela já estão certos e podem ser
                conferidos por aqui enquanto isso.
            </Aviso>
        </div>

        <template #footer>
            <Button label="Fechar" severity="secondary" outlined size="small" @click="fechar" />
            <Button
                label="Exportar"
                size="small"
                disabled
                title="A geração de Excel e PDF ainda não existe no backend"
            >
                <template #icon><Icone nome="download" :tamanho="16" /></template>
            </Button>
        </template>
    </Dialog>
</template>
