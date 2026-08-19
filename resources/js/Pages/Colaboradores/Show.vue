<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import ContasBancarias from '@/Components/ContasBancarias.vue';
import { useFormato } from '@/Composables/useFormato';
import { usePermissoes } from '@/Composables/usePermissoes';
import type { Colaborador, Opcao } from '@/types';

const props = defineProps<{
    colaborador: Colaborador & { usuario?: { id: number; name: string; email: string } | null };
    pagamentos: Array<{
        id: number;
        descricao: string;
        valor: string;
        data_vencimento: string;
        status: string;
        categoria?: { nome: string };
    }>;
    opcoes: { tipoConta: Opcao[]; tipoChavePix: Opcao[] };
}>();

const { formatarMoeda, formatarData, formatarDocumento } = useFormato();
const { pode } = usePermissoes();

const campos = [
    { rotulo: 'CPF', valor: formatarDocumento(props.colaborador.cpf) },
    { rotulo: 'Cargo', valor: props.colaborador.cargo },
    { rotulo: 'Departamento', valor: props.colaborador.departamento },
    { rotulo: 'Tipo de contrato', valor: props.colaborador.tipo_contrato },
    { rotulo: 'Admissão', valor: formatarData(props.colaborador.data_admissao) },
    { rotulo: 'Salário base', valor: formatarMoeda(props.colaborador.salario_base) },
    { rotulo: 'E-mail', valor: props.colaborador.email ?? '—' },
    { rotulo: 'Telefone', valor: props.colaborador.telefone ?? '—' },
];
</script>

<template>
    <Head :title="colaborador.nome" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina :titulo="colaborador.nome" :descricao="colaborador.cargo">
                <template #acoes>
                    <StatusBadge :status="colaborador.status" />
                    <Link
                        v-if="pode('colaboradores.gerenciar')"
                        :href="route('colaboradores.edit', colaborador.id)"
                    >
                        <Button label="Editar" icon="pi pi-pencil" size="small" outlined />
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-black/5 bg-white p-6">
                    <h2 class="mb-5 text-sm font-semibold text-corebanx-preto">
                        Dados cadastrais
                    </h2>

                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div v-for="campo in campos" :key="campo.rotulo">
                            <dt class="text-xs font-medium uppercase tracking-wide text-corebanx-preto/40">
                                {{ campo.rotulo }}
                            </dt>
                            <dd class="mt-0.5 text-sm text-corebanx-preto">
                                {{ campo.valor }}
                            </dd>
                        </div>
                    </dl>

                    <p
                        v-if="colaborador.data_desligamento"
                        class="mt-5 rounded-lg bg-corebanx-cinza px-4 py-3 text-sm text-corebanx-preto/70"
                    >
                        Desligado em {{ formatarData(colaborador.data_desligamento) }}. O
                        histórico foi preservado e novos lançamentos de folha estão
                        bloqueados.
                    </p>
                </div>

                <div class="overflow-hidden rounded-xl border border-black/5 bg-white">
                    <div class="border-b border-black/5 px-6 py-4">
                        <h2 class="text-sm font-semibold text-corebanx-preto">
                            Últimos pagamentos
                        </h2>
                    </div>

                    <DataTable :value="pagamentos" size="small" data-key="id">
                        <template #empty>
                            <p class="py-8 text-center text-sm text-corebanx-preto/45">
                                Nenhum pagamento lançado.
                            </p>
                        </template>

                        <Column field="descricao" header="Descrição">
                            <template #body="{ data }">
                                <Link
                                    :href="route('pagamentos.show', data.id)"
                                    class="hover:text-corebanx-laranja"
                                >
                                    {{ data.descricao }}
                                </Link>
                            </template>
                        </Column>
                        <Column header="Categoria">
                            <template #body="{ data }">
                                {{ data.categoria?.nome ?? '—' }}
                            </template>
                        </Column>
                        <Column header="Vencimento">
                            <template #body="{ data }">
                                <span class="tabular-nums">
                                    {{ formatarData(data.data_vencimento) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Valor">
                            <template #body="{ data }">
                                <span class="tabular-nums">
                                    {{ formatarMoeda(data.valor) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">
                                <StatusBadge :status="data.status" />
                            </template>
                        </Column>
                    </DataTable>
                </div>
            </div>

            <ContasBancarias
                tipo-beneficiario="colaborador"
                :beneficiario-id="colaborador.id"
                :contas="colaborador.contas_bancarias ?? []"
                :opcoes="opcoes"
            />
        </div>
    </AuthenticatedLayout>
</template>
