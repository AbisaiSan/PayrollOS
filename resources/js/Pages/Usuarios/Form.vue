<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import type { Opcao } from '@/types';

interface UsuarioEmEdicao {
    id: number;
    name: string;
    email: string;
    colaborador_id: number | null;
    ativo: boolean;
    perfil: string | null;
}

interface ColaboradorOpcao {
    id: number;
    nome: string;
    departamento: string;
}

const props = defineProps<{
    usuario: UsuarioEmEdicao | null;
    opcoes: { perfis: Opcao[]; colaboradores: ColaboradorOpcao[] };
}>();

const editando = props.usuario !== null;

/** O que cada perfil enxerga, para a escolha não depender de memória. */
const RESUMO_PERFIL: Record<string, string> = {
    administrador: 'Acesso total, incluindo a gestão de contas de acesso.',
    financeiro: 'Lança e confirma pagamentos, aprova reembolsos, mexe em cadastros e relatórios.',
    gestor: 'Enxerga tudo e solicita reembolso, mas não lança pagamento nem edita cadastro.',
    leitura: 'Só consulta. Não altera nada em nenhum módulo.',
};

const form = useForm({
    name: props.usuario?.name ?? '',
    email: props.usuario?.email ?? '',
    password: '',
    password_confirmation: '',
    perfil: props.usuario?.perfil ?? 'leitura',
    colaborador_id: props.usuario?.colaborador_id ?? null,
    ativo: props.usuario?.ativo ?? true,
});

const resumoDoPerfil = computed(() => RESUMO_PERFIL[form.perfil] ?? '');

const enviar = () => {
    if (editando && props.usuario) {
        form.put(route('usuarios.update', props.usuario.id));

        return;
    }

    form.post(route('usuarios.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar conta' : 'Nova conta de acesso'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar conta' : 'Nova conta de acesso'"
                :descricao="editando ? usuario?.email : 'Quem entra no sistema e com qual perfil'"
            />
        </template>

        <div class="max-w-[760px]">
            <Link
                :href="route('usuarios.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para usuários
            </Link>

            <form class="space-y-4" @submit.prevent="enviar">
                <!-- Identificação -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Identificação</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="name" class="text-[12.75px] font-semibold text-ink-90">
                                Nome <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.name"
                            />
                            <Message
                                v-if="form.errors.name"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.name }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-[12.75px] font-semibold text-ink-90">
                                E-mail <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                class="w-full"
                                autocomplete="off"
                                :invalid="!!form.errors.email"
                            />
                            <span class="text-[11.5px] text-ink-55">É com ele que a pessoa entra</span>
                            <Message
                                v-if="form.errors.email"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.email }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="password" class="text-[12.75px] font-semibold text-ink-90">
                                Senha
                                <span v-if="!editando" class="text-laranja-600">●</span>
                            </label>
                            <Password
                                v-model="form.password"
                                input-id="password"
                                toggle-mask
                                fluid
                                autocomplete="new-password"
                                :invalid="!!form.errors.password"
                            />
                            <span v-if="editando" class="text-[11.5px] text-ink-55">
                                Em branco, mantém a senha atual
                            </span>
                            <Message
                                v-if="form.errors.password"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.password }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label
                                for="password_confirmation"
                                class="text-[12.75px] font-semibold text-ink-90"
                            >
                                Confirmar senha
                            </label>
                            <Password
                                v-model="form.password_confirmation"
                                input-id="password_confirmation"
                                :feedback="false"
                                toggle-mask
                                fluid
                                autocomplete="new-password"
                            />
                        </div>
                    </div>
                </div>

                <!-- Acesso -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Acesso</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="perfil" class="text-[12.75px] font-semibold text-ink-90">
                                Perfil <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="perfil"
                                v-model="form.perfil"
                                :options="opcoes.perfis"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.perfil"
                            />
                            <span v-if="resumoDoPerfil" class="text-[11.5px] text-azul-600">
                                {{ resumoDoPerfil }}
                            </span>
                            <Message
                                v-if="form.errors.perfil"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.perfil }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="colaborador" class="text-[12.75px] font-semibold text-ink-90">
                                Colaborador vinculado
                            </label>
                            <Select
                                id="colaborador"
                                v-model="form.colaborador_id"
                                :options="opcoes.colaboradores"
                                option-label="nome"
                                option-value="id"
                                placeholder="Sem vínculo"
                                show-clear
                                filter
                                filter-placeholder="Buscar colaborador…"
                                class="w-full"
                                :invalid="!!form.errors.colaborador_id"
                            >
                                <template #option="{ option }">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-medium">{{ option.nome }}</span>
                                        <span class="text-[11.5px] text-ink-55">
                                            {{ option.departamento }}
                                        </span>
                                    </div>
                                </template>
                            </Select>
                            <span class="text-[11.5px] text-ink-55">
                                Opcional · só colaboradores que ainda não têm conta
                            </span>
                            <Message
                                v-if="form.errors.colaborador_id"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.colaborador_id }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="flex items-center gap-2 text-[13px]">
                                <Checkbox v-model="form.ativo" input-id="ativo" binary />
                                <span>Conta ativa</span>
                            </label>
                            <span class="text-[11.5px] text-ink-55">
                                Desativada, a pessoa não consegue entrar. O que ela já fez continua
                                registrado.
                            </span>
                        </div>
                    </div>

                    <Aviso class="mt-4">
                        Não existe cadastro público neste sistema. Esta tela é a única porta de
                        entrada de uma conta nova — e o perfil escolhido define o que a pessoa vê
                        no menu.
                    </Aviso>
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('usuarios.index')">
                            <Button
                                label="Cancelar"
                                severity="secondary"
                                outlined
                                size="small"
                                :disabled="form.processing"
                            />
                        </Link>
                        <Button
                            type="submit"
                            :label="editando ? 'Salvar alterações' : 'Criar conta'"
                            size="small"
                            :loading="form.processing"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
