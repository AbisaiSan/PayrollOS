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
 * O download não passa pelo Inertia: a resposta é um arquivo, não uma página, e
 * um visit do router tentaria interpretá-la como navegação. Por isso a ação é um
 * link comum com os filtros na query string, e a rota devolve o arquivo direto.
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

/** Os mesmos filtros da tela, que é o que o modal promete no cabeçalho. */
const enderecoDoArquivo = computed(() =>
    route('relatorios.exportar', {
        formato: formato.value,
        inicio: props.filtros.inicio,
        fim: props.filtros.fim,
        categoria_id: props.filtros.categoria_id ?? undefined,
        status: props.filtros.status ?? undefined,
    }),
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

            <Aviso>
                O arquivo carrega os agregados que estão na tela e também a lista dos
                lançamentos que os sustentam — pagamentos e reembolsos —, com o período e os
                filtros escritos no cabeçalho.
            </Aviso>
        </div>

        <template #footer>
            <Button label="Cancelar" severity="secondary" outlined size="small" @click="fechar" />
            <a :href="enderecoDoArquivo" @click="fechar">
                <Button label="Exportar" size="small">
                    <template #icon><Icone nome="download" :tamanho="16" /></template>
                </Button>
            </a>
        </template>
    </Dialog>
</template>
