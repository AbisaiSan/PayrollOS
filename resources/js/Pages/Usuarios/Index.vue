<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import { useConfirm } from 'primevue/useconfirm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icone from '@/Components/Icone.vue';
import EstadoListagem from '@/Components/EstadoListagem.vue';
import TabelaEsqueleto from '@/Components/TabelaEsqueleto.vue';
import { debounce } from '@/Composables/useDebounce';
import { useConsulta } from '@/Composables/useConsulta';
import type { Opcao, PageProps, Paginado } from '@/types';

interface LinhaUsuario {
    id: number;
    name: string;
    email: string;
    ativo: boolean;
    perfil: string | null;
    perfil_rotulo: string | null;
    colaborador: { id: number; nome: string; departamento: string } | null;
}

const props = defineProps<{
    usuarios: Paginado<LinhaUsuario>;
    filtros: Record<string, string | undefined>;
    opcoes: { perfis: Opcao[] };
}>();

const confirm = useConfirm();
const usuarioAtual = usePage<PageProps>().props.auth.user;

const busca = ref(props.filtros.busca ?? '');
const perfil = ref(props.filtros.perfil ?? null);
const ativo = ref(props.filtros.ativo ?? null);

const OPCOES_ATIVO = [
    { value: '1', label: 'Ativos' },
    { value: '0', label: 'Desativados' },
];

const temFiltro = computed(() => !!(busca.value || perfil.value || ativo.value));

const { carregando, erro, consultar: visitar, tentarNovamente } = useConsulta(
    route('usuarios.index'),
);

const consultar = (extras: Record<string, unknown> = {}) => {
    visitar({
        busca: busca.value || undefined,
        perfil: perfil.value || undefined,
        ativo: ativo.value ?? undefined,
        ...extras,
    });
};

const consultarComDebounce = debounce(() => consultar(), 350);

watch(busca, () => consultarComDebounce());
watch([perfil, ativo], () => consultar());

const limparFiltros = () => {
    busca.value = '';
    perfil.value = null;
    ativo.value = null;
};

const mudarPagina = (evento: { page: number; rows: number }) => {
    consultar({ page: evento.page + 1, por_pagina: evento.rows });
};

const irParaCadastro = () => router.get(route('usuarios.create'));

/**
 * Desativar a própria conta tiraria o acesso de quem está no meio da ação — e se
 * fosse o único administrador, ninguém mais conseguiria reativar ninguém. A
 * policy recusa; aqui o botão nem aparece.
 */
const ehVoce = (usuario: LinhaUsuario) => usuario.id === usuarioAtual.id;

const alternarAtivo = (usuario: LinhaUsuario) => {
    if (usuario.ativo) {
        confirm.require({
            header: 'Desativar acesso',
            message: `${usuario.name} deixa de conseguir entrar no sistema. Nada do que essa pessoa já fez é apagado, e o acesso pode ser devolvido depois.`,
            acceptLabel: 'Desativar acesso',
            rejectLabel: 'Voltar',
            acceptProps: { severity: 'danger', size: 'small' },
            rejectProps: { severity: 'secondary', text: true, size: 'small' },
            accept: () =>
                router.post(route('usuarios.ativo', usuario.id), {}, { preserveScroll: true }),
        });

        return;
    }

    router.post(route('usuarios.ativo', usuario.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Usuários" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                titulo="Usuários"
                descricao="Contas de acesso ao sistema — não confundir com o cadastro de colaboradores"
            >
                <template #acoes>
                    <Link :href="route('usuarios.create')">
                        <Button label="Nova conta" size="small">
                            <template #icon><Icone nome="plus" :tamanho="16" /></template>
                        </Button>
                    </Link>
                </template>
            </CabecalhoPagina>
        </template>

        <!-- Filtros -->
        <div class="mb-4 flex flex-wrap items-center gap-2.5">
            <span class="relative min-w-[240px] flex-1 sm:max-w-sm">
                <Icone
                    nome="search"
                    :tamanho="15"
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-35"
                />
                <InputText
                    v-model="busca"
                    placeholder="Buscar por nome ou e-mail…"
                    class="w-full !pl-9"
                    size="small"
                    aria-label="Buscar por nome ou e-mail"
                />
            </span>

            <Select
                v-model="perfil"
                :options="opcoes.perfis"
                option-label="label"
                option-value="value"
                placeholder="Perfil — todos"
                show-clear
                size="small"
                class="w-[180px]"
                aria-label="Filtrar por perfil"
            />

            <Select
                v-model="ativo"
                :options="OPCOES_ATIVO"
                option-label="label"
                option-value="value"
                placeholder="Situação — todas"
                show-clear
                size="small"
                class="w-[168px]"
                aria-label="Filtrar por situação"
            />

            <button
                v-if="temFiltro"
                type="button"
                class="flex items-center gap-1 px-1 py-1.5 text-[12.5px] font-semibold text-laranja-600 hover:underline"
                @click="limparFiltros"
            >
                <Icone nome="x" :tamanho="13" />
                Limpar filtros
            </button>
        </div>

        <!-- Grid -->
        <div class="overflow-hidden rounded-lg border border-ink-8 bg-white shadow-card">
            <TabelaEsqueleto v-if="carregando" :colunas="5" />

            <EstadoListagem
                v-else-if="erro"
                variante="erro"
                titulo="Não foi possível carregar as contas"
                descricao="Verifique sua conexão e tente novamente. Se o problema continuar, contate o suporte."
                acao="Tentar novamente"
                @acao="tentarNovamente"
            />

            <DataTable
                v-else
                :value="usuarios.data"
                data-key="id"
                size="small"
                lazy
                paginator
                :rows="usuarios.per_page"
                :total-records="usuarios.total"
                :first="(usuarios.current_page - 1) * usuarios.per_page"
                :rows-per-page-options="[20, 50, 100]"
                paginator-template="CurrentPageReport PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                current-page-report-template="Mostrando {first}–{last} de {totalRecords}"
                @page="mudarPagina"
            >
                <template #empty>
                    <EstadoListagem
                        v-if="temFiltro"
                        variante="vazio-filtro"
                        titulo="Nenhuma conta encontrada para estes filtros"
                        descricao="Ajuste os filtros aplicados ou limpe-os para ver todas as contas."
                        acao="Limpar filtros"
                        @acao="limparFiltros"
                    />
                    <EstadoListagem
                        v-else
                        variante="vazio"
                        icone="shield"
                        titulo="Nenhuma conta cadastrada ainda"
                        descricao="Não há cadastro público: toda conta de acesso nasce aqui."
                        acao="Criar a primeira conta"
                        @acao="irParaCadastro"
                    />
                </template>

                <Column field="name" header="Nome">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-ink">{{ data.name }}</span>
                            <span
                                v-if="ehVoce(data)"
                                class="rounded-full bg-azul-50 px-[7px] py-px text-[10.5px] font-semibold text-azul-600"
                            >
                                você
                            </span>
                        </div>
                        <div class="mt-0.5 text-[12px] text-ink-55">{{ data.email }}</div>
                    </template>
                </Column>

                <Column field="perfil" header="Perfil">
                    <template #body="{ data }">
                        {{ data.perfil_rotulo ?? '—' }}
                    </template>
                </Column>

                <!--
                    O vínculo com colaborador é opcional nos dois sentidos: nem todo
                    usuário está na folha, e nem todo colaborador tem acesso.
                -->
                <Column field="colaborador" header="Colaborador vinculado">
                    <template #body="{ data }">
                        <template v-if="data.colaborador">
                            {{ data.colaborador.nome }}
                            <div class="mt-0.5 text-[12px] text-ink-55">
                                {{ data.colaborador.departamento }}
                            </div>
                        </template>
                        <span v-else class="text-ink-35">Sem vínculo</span>
                    </template>
                </Column>

                <Column field="ativo" header="Situação">
                    <template #body="{ data }">
                        <StatusBadge :status="data.ativo ? 'ativo' : 'inativo'" />
                    </template>
                </Column>

                <Column class="!text-right" header-class="!text-right" style="width: 1%">
                    <template #body="{ data }">
                        <div class="flex items-center justify-end gap-0.5">
                            <Link :href="route('usuarios.edit', data.id)">
                                <button
                                    type="button"
                                    class="rounded-lg p-2 text-ink-70 hover:bg-ink-8"
                                    :title="`Editar ${data.name}`"
                                >
                                    <Icone nome="edit" :tamanho="15" />
                                </button>
                            </Link>

                            <button
                                v-if="!ehVoce(data)"
                                type="button"
                                class="rounded-lg p-2"
                                :class="
                                    data.ativo
                                        ? 'text-ink-55 hover:bg-ink-8 hover:text-perigo'
                                        : 'text-azul-600 hover:bg-ink-8'
                                "
                                :title="
                                    data.ativo
                                        ? `Desativar acesso de ${data.name}`
                                        : `Reativar acesso de ${data.name}`
                                "
                                @click="alternarAtivo(data)"
                            >
                                <Icone :nome="data.ativo ? 'slashCircle' : 'refresh'" :tamanho="15" />
                            </button>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AuthenticatedLayout>
</template>
