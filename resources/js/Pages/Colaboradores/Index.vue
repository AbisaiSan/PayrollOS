<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from '@/Composables/useDebounce';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Colaborador, Opcao, Paginado } from '@/types';

const props = defineProps<{
    colaboradores: Paginado<Colaborador & { contas_bancarias_count: number }>;
    filtros: Record<string, string | undefined>;
    opcoes: { status: Opcao[]; departamentos: string[] };
}>();

const { formatarMoeda, formatarDocumento, formatarData } = useFormato();
const { pode } = usePermissoes();

const busca = ref(props.filtros.busca ?? '');
const status = ref(props.filtros.status ?? null);
const departamento = ref(props.filtros.departamento ?? null);

const aplicarFiltros = () => {
    router.get(
        route('colaboradores.index'),
        {
            busca: busca.value || undefined,
            status: status.value || undefined,
            departamento: departamento.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

// Debounce so na busca por texto; os selects aplicam na hora.
watch(busca, debounce(aplicarFiltros, 350));
watch([status, departamento], aplicarFiltros);

const mudarPagina = (evento: { page: number; rows: number }) => {
    router.get(
        route('colaboradores.index'),
        {
            ...props.filtros,
            page: evento.page + 1,
            por_pagina: evento.rows,
        },
        { preserveState: true, replace: true },
    );
};
</script>

<template>
    <Head title="Colaboradores" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Colaboradores"
                :descricao="`${colaboradores.total} cadastrado(s)`"
            >
                <template #acoes>
                    <Link
                        v-if="pode('colaboradores.gerenciar')"
                        :href="route('colaboradores.create')"
                    >
                        <Button label="Novo colaborador" icon="pi pi-plus" size="small" />
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-3 rounded-xl border border-black/5 bg-white p-4">
                <InputText
                    v-model="busca"
                    placeholder="Buscar por nome, CPF, cargo ou e-mail"
                    class="min-w-64 flex-1"
                    size="small"
                />
                <Select
                    v-model="status"
                    :options="opcoes.status"
                    option-label="label"
                    option-value="value"
                    placeholder="Status"
                    show-clear
                    size="small"
                    class="w-44"
                />
                <Select
                    v-model="departamento"
                    :options="opcoes.departamentos"
                    placeholder="Departamento"
                    show-clear
                    size="small"
                    class="w-52"
                />
            </div>

            <div class="overflow-hidden rounded-xl border border-black/5 bg-white">
                <DataTable
                    :value="colaboradores.data"
                    data-key="id"
                    size="small"
                    lazy
                    paginator
                    :rows="colaboradores.per_page"
                    :total-records="colaboradores.total"
                    :first="(colaboradores.current_page - 1) * colaboradores.per_page"
                    :rows-per-page-options="[15, 30, 50]"
                    @page="mudarPagina"
                >
                    <template #empty>
                        <p class="py-10 text-center text-sm text-corebanx-preto/45">
                            Nenhum colaborador encontrado.
                        </p>
                    </template>

                    <Column field="nome" header="Nome">
                        <template #body="{ data }">
                            <Link
                                :href="route('colaboradores.show', data.id)"
                                class="font-medium text-corebanx-preto hover:text-corebanx-laranja"
                            >
                                {{ data.nome }}
                            </Link>
                            <p class="text-xs text-corebanx-preto/50">
                                {{ formatarDocumento(data.cpf) }}
                            </p>
                        </template>
                    </Column>

                    <Column field="cargo" header="Cargo">
                        <template #body="{ data }">
                            {{ data.cargo }}
                            <p class="text-xs text-corebanx-preto/50">
                                {{ data.departamento }}
                            </p>
                        </template>
                    </Column>

                    <Column field="tipo_contrato" header="Contrato" />

                    <Column field="data_admissao" header="Admissão">
                        <template #body="{ data }">
                            <span class="tabular-nums">
                                {{ formatarData(data.data_admissao) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="salario_base" header="Salário base">
                        <template #body="{ data }">
                            <span class="tabular-nums">
                                {{ formatarMoeda(data.salario_base) }}
                            </span>
                        </template>
                    </Column>

                    <Column field="contas_bancarias_count" header="Contas">
                        <template #body="{ data }">
                            <span
                                class="text-sm"
                                :class="
                                    data.contas_bancarias_count === 0
                                        ? 'text-red-600'
                                        : 'text-corebanx-preto/60'
                                "
                            >
                                {{ data.contas_bancarias_count }}
                            </span>
                        </template>
                    </Column>

                    <Column field="status" header="Status">
                        <template #body="{ data }">
                            <StatusBadge :status="data.status" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
