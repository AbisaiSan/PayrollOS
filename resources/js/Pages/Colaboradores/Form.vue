<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Colaborador, Opcao } from '@/types';

const props = defineProps<{
    colaborador: Colaborador | null;
    opcoes: { tipoContrato: Opcao[]; status: Opcao[] };
}>();

const editando = props.colaborador !== null;
const { paraDate, paraIso } = useFormato();

const form = useForm({
    nome: props.colaborador?.nome ?? '',
    cpf: props.colaborador?.cpf ?? '',
    cargo: props.colaborador?.cargo ?? '',
    departamento: props.colaborador?.departamento ?? '',
    tipo_contrato: props.colaborador?.tipo_contrato ?? 'clt',
    data_admissao: paraDate(props.colaborador?.data_admissao),
    data_desligamento: paraDate(props.colaborador?.data_desligamento),
    salario_base: props.colaborador ? Number(props.colaborador.salario_base) : 0,
    email: props.colaborador?.email ?? '',
    telefone: props.colaborador?.telefone ?? '',
    status: props.colaborador?.status ?? 'ativo',
    observacoes: '',
});

const enviar = () => {
    // O DatePicker devolve Date; o backend valida AAAA-MM-DD.
    const dados = form.transform((campos) => ({
        ...campos,
        data_admissao: paraIso(campos.data_admissao),
        data_desligamento: paraIso(campos.data_desligamento),
    }));

    if (editando && props.colaborador) {
        dados.put(route('colaboradores.update', props.colaborador.id));

        return;
    }

    dados.post(route('colaboradores.store'));
};
</script>

<template>
    <Head :title="editando ? 'Editar colaborador' : 'Novo colaborador'" />

    <AuthenticatedLayout>
        <template #header>
            <CabecalhoPagina
                :titulo="editando ? 'Editar colaborador' : 'Novo colaborador'"
                :descricao="editando ? colaborador?.nome : 'Dados cadastrais e de folha'"
            />
        </template>

        <form class="max-w-3xl space-y-6" @submit.prevent="enviar">
            <div class="rounded-xl border border-black/5 bg-white p-6">
                <h2 class="mb-5 text-sm font-semibold text-corebanx-preto">
                    Dados pessoais
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Nome completo</label>
                        <InputText v-model="form.nome" class="w-full" :invalid="!!form.errors.nome" />
                        <Message v-if="form.errors.nome" severity="error" size="small" variant="simple">
                            {{ form.errors.nome }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">CPF</label>
                        <InputText
                            v-model="form.cpf"
                            class="w-full"
                            placeholder="000.000.000-00"
                            :invalid="!!form.errors.cpf"
                        />
                        <Message v-if="form.errors.cpf" severity="error" size="small" variant="simple">
                            {{ form.errors.cpf }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">E-mail</label>
                        <InputText v-model="form.email" class="w-full" :invalid="!!form.errors.email" />
                        <Message v-if="form.errors.email" severity="error" size="small" variant="simple">
                            {{ form.errors.email }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Telefone</label>
                        <InputText v-model="form.telefone" class="w-full" />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-black/5 bg-white p-6">
                <h2 class="mb-5 text-sm font-semibold text-corebanx-preto">
                    Vínculo
                </h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cargo</label>
                        <InputText v-model="form.cargo" class="w-full" :invalid="!!form.errors.cargo" />
                        <Message v-if="form.errors.cargo" severity="error" size="small" variant="simple">
                            {{ form.errors.cargo }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Departamento</label>
                        <InputText
                            v-model="form.departamento"
                            class="w-full"
                            :invalid="!!form.errors.departamento"
                        />
                        <Message
                            v-if="form.errors.departamento"
                            severity="error"
                            size="small"
                            variant="simple"
                        >
                            {{ form.errors.departamento }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tipo de contrato</label>
                        <Select
                            v-model="form.tipo_contrato"
                            :options="opcoes.tipoContrato"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Status</label>
                        <Select
                            v-model="form.status"
                            :options="opcoes.status"
                            option-label="label"
                            option-value="value"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Data de admissão</label>
                        <DatePicker
                            v-model="form.data_admissao"
                            date-format="dd/mm/yy"
                            class="w-full"
                            :invalid="!!form.errors.data_admissao"
                        />
                        <Message
                            v-if="form.errors.data_admissao"
                            severity="error"
                            size="small"
                            variant="simple"
                        >
                            {{ form.errors.data_admissao }}
                        </Message>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Salário base</label>
                        <InputNumber
                            v-model="form.salario_base"
                            mode="currency"
                            currency="BRL"
                            locale="pt-BR"
                            class="w-full"
                            :invalid="!!form.errors.salario_base"
                        />
                        <Message
                            v-if="form.errors.salario_base"
                            severity="error"
                            size="small"
                            variant="simple"
                        >
                            {{ form.errors.salario_base }}
                        </Message>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Observações</label>
                        <Textarea v-model="form.observacoes" rows="3" class="w-full" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('colaboradores.index')">
                    <Button label="Cancelar" severity="secondary" text size="small" />
                </Link>
                <Button
                    type="submit"
                    :label="editando ? 'Salvar alterações' : 'Cadastrar'"
                    :loading="form.processing"
                    size="small"
                />
            </div>
        </form>
    </AuthenticatedLayout>
</template>
