<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import ModalCategoria from '@/Components/ModalCategoria.vue';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { CategoriaPagamento, Opcao } from '@/types';

type LinhaCategoria = CategoriaPagamento & { pagamentos_count: number };

const props = defineProps<{
    /** Lista completa: a tabela é curta e não pagina no servidor. */
    categorias: LinhaCategoria[];
    filtros: Record<string, string | undefined>;
    opcoes: { tipo: Opcao[] };
}>();

const { pode } = usePermissoes();

const tipo = ref(props.filtros.tipo ?? null);

const ROTULO_TIPO = computed(() =>
    Object.fromEntries(props.opcoes.tipo.map((opcao) => [opcao.value, opcao.label])),
);

const dialogAberto = ref(false);
const categoriaEmEdicao = ref<LinhaCategoria | null>(null);

const abrirDialog = (categoria: LinhaCategoria | null = null) => {
    categoriaEmEdicao.value = categoria;
    dialogAberto.value = true;
};

watch(tipo, () => {
    router.get(
        route('categorias.index'),
        { tipo: tipo.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
});
</script>

<template>
    <Head title="Categorias" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Categorias"
                descricao="Classificação usada em pagamentos e relatórios"
            >
                <template #acoes>
                    <Button
                        v-if="pode('categorias.gerenciar')"
                        label="Nova categoria"
                        size="small"
                        @click="abrirDialog()"
                    >
                        <template #icon><Icone nome="plus" :tamanho="16" /></template>
                    </Button>
                </template>
            </CabecalhoPagina>
        </template>

        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <Select
                v-model="tipo"
                :options="opcoes.tipo"
                option-label="label"
                option-value="value"
                placeholder="Tipo — todos"
                show-clear
                size="small"
                class="w-[200px]"
                aria-label="Filtrar por tipo"
            />
        </div>

        <!--
            Sem paginação, de propósito: são doze linhas. O aparato das listagens
            pesadas (busca, período, seletor de página) custaria mais atenção do
            que a tabela inteira.
        -->
        <div class="max-w-[880px] overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
            <DataTable :value="categorias" data-key="id" size="small">
                <template #empty>
                    <p class="py-12 text-center text-[13px] text-ink-55">
                        {{
                            tipo
                                ? 'Nenhuma categoria desse tipo.'
                                : 'Nenhuma categoria cadastrada ainda.'
                        }}
                    </p>
                </template>

                <Column field="nome" header="Nome">
                    <template #body="{ data }">
                        <div class="font-semibold text-ink">{{ data.nome }}</div>
                        <div v-if="data.descricao" class="mt-0.5 text-[12px] text-ink-55">
                            {{ data.descricao }}
                        </div>
                    </template>
                </Column>

                <Column field="tipo" header="Tipo">
                    <template #body="{ data }">
                        {{ ROTULO_TIPO[data.tipo] ?? data.tipo }}
                    </template>
                </Column>

                <Column
                    field="pagamentos_count"
                    header="Pagamentos"
                    class="text-right"
                    header-class="!text-right"
                >
                    <template #body="{ data }">
                        <span
                            class="mono inline-block rounded-full bg-ink-8 px-2 py-0.5 text-[12.25px] font-semibold text-ink-70"
                        >
                            {{ data.pagamentos_count }}
                        </span>
                    </template>
                </Column>

                <Column field="ativo" header="Status">
                    <template #body="{ data }">
                        <StatusBadge :status="data.ativo ? 'ativo' : 'inativo'" />
                    </template>
                </Column>

                <Column
                    v-if="pode('categorias.gerenciar')"
                    class="!text-right"
                    header-class="!text-right"
                    style="width: 1%"
                >
                    <template #body="{ data }">
                        <button
                            type="button"
                            class="rounded-lg p-2 text-ink-70 hover:bg-ink-8"
                            :title="`Editar ${data.nome}`"
                            aria-label="Editar categoria"
                            @click="abrirDialog(data)"
                        >
                            <Icone nome="edit" :tamanho="15" />
                        </button>
                    </template>
                </Column>
            </DataTable>
        </div>

        <p class="mt-2.5 max-w-[880px] text-[11.75px] text-ink-55">
            Categoria em uso não é excluída — desativá-la a tira dos formulários sem mexer nos
            pagamentos já classificados com ela.
        </p>

        <ModalCategoria
            v-model:visivel="dialogAberto"
            :categoria="categoriaEmEdicao"
            :opcoes="opcoes"
        />
    </AuthenticatedLayout>
</template>
