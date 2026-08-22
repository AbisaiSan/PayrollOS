<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import InputMask from 'primevue/inputmask';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Message from 'primevue/message';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CabecalhoPagina from '@/Components/CabecalhoPagina.vue';
import Aviso from '@/Components/Aviso.vue';
import Icone from '@/Components/Icone.vue';
import { useFormato } from '@/Composables/useFormato';
import type { Colaborador, Opcao } from '@/types';

const props = defineProps<{
    colaborador: Colaborador | null;
    opcoes: { tipoContrato: Opcao[]; status: Opcao[] };
}>();

const { paraDate, paraIso, formatarDocumento, cpfValido } = useFormato();

const editando = props.colaborador !== null;

const form = useForm({
    nome: props.colaborador?.nome ?? '',
    // O banco guarda só dígitos; a máscara trabalha com o CPF formatado.
    cpf: formatarDocumento(props.colaborador?.cpf) === '—' ? '' : formatarDocumento(props.colaborador?.cpf),
    email: props.colaborador?.email ?? '',
    telefone: props.colaborador?.telefone ?? '',
    cargo: props.colaborador?.cargo ?? '',
    departamento: props.colaborador?.departamento ?? '',
    tipo_contrato: props.colaborador?.tipo_contrato ?? 'clt',
    status: props.colaborador?.status ?? 'ativo',
    data_admissao: paraDate(props.colaborador?.data_admissao),
    salario_base: props.colaborador ? Number(props.colaborador.salario_base) : null,
    observacoes: props.colaborador?.observacoes ?? '',
});

/**
 * O dígito verificador é checado enquanto se digita, mas o aviso só aparece com
 * os 11 dígitos completos: reclamar de um CPF pela metade é ruído, não ajuda.
 */
const cpfPreenchido = computed(() => form.cpf.replace(/\D/g, '').length === 11);
const cpfInvalido = computed(() => cpfPreenchido.value && !cpfValido(form.cpf));

const vaiDesligar = computed(() => form.status === 'desligado');

const enviar = () => {
    // O DatePicker devolve Date; o backend valida AAAA-MM-DD.
    const dados = form.transform((campos) => ({
        ...campos,
        data_admissao: paraIso(campos.data_admissao),
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
                :descricao="editando ? colaborador?.nome : 'Cadastro e vínculo de folha'"
            />
        </template>

        <div class="max-w-[960px]">
            <Link
                :href="route('colaboradores.index')"
                class="mb-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[12.25px] font-semibold text-ink-70 hover:bg-ink-8"
            >
                <Icone nome="chevronLeft" :tamanho="15" />
                Voltar para colaboradores
            </Link>

            <form class="space-y-4" @submit.prevent="enviar">
                <!-- Dados pessoais -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Dados pessoais</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label for="nome" class="text-[12.75px] font-semibold text-ink-90">
                                Nome completo <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="nome"
                                v-model="form.nome"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.nome"
                            />
                            <Message
                                v-if="form.errors.nome"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.nome }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="cpf" class="text-[12.75px] font-semibold text-ink-90">
                                CPF <span class="text-laranja-600">●</span>
                            </label>
                            <InputMask
                                id="cpf"
                                v-model="form.cpf"
                                mask="999.999.999-99"
                                placeholder="000.000.000-00"
                                class="w-full"
                                input-class="mono"
                                :invalid="!!form.errors.cpf || cpfInvalido"
                            />
                            <Message
                                v-if="form.errors.cpf"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.cpf }}
                            </Message>
                            <Message
                                v-else-if="cpfInvalido"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                Dígito verificador não confere — revise o número.
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="email" class="text-[12.75px] font-semibold text-ink-90">
                                E-mail
                            </label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.email"
                            />
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
                            <label for="telefone" class="text-[12.75px] font-semibold text-ink-90">
                                Telefone
                            </label>
                            <InputMask
                                id="telefone"
                                v-model="form.telefone"
                                mask="(99) 99999-9999"
                                placeholder="(00) 00000-0000"
                                :auto-clear="false"
                                class="w-full"
                                :invalid="!!form.errors.telefone"
                            />
                            <Message
                                v-if="form.errors.telefone"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.telefone }}
                            </Message>
                        </div>
                    </div>
                </div>

                <!-- Vínculo -->
                <div class="rounded-lg border border-ink-8 bg-white p-5 shadow-card">
                    <h3 class="mb-4 text-[14px] font-semibold">Vínculo</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label for="cargo" class="text-[12.75px] font-semibold text-ink-90">
                                Cargo <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="cargo"
                                v-model="form.cargo"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.cargo"
                            />
                            <Message
                                v-if="form.errors.cargo"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.cargo }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label
                                for="departamento"
                                class="text-[12.75px] font-semibold text-ink-90"
                            >
                                Departamento <span class="text-laranja-600">●</span>
                            </label>
                            <InputText
                                id="departamento"
                                v-model="form.departamento"
                                maxlength="255"
                                class="w-full"
                                :invalid="!!form.errors.departamento"
                            />
                            <span class="text-[11.5px] text-ink-55">
                                Alimenta o filtro de departamento na listagem
                            </span>
                            <Message
                                v-if="form.errors.departamento"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.departamento }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="tipo" class="text-[12.75px] font-semibold text-ink-90">
                                Tipo de contrato <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="tipo"
                                v-model="form.tipo_contrato"
                                :options="opcoes.tipoContrato"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.tipo_contrato"
                            />
                            <Message
                                v-if="form.errors.tipo_contrato"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.tipo_contrato }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="status" class="text-[12.75px] font-semibold text-ink-90">
                                Status <span class="text-laranja-600">●</span>
                            </label>
                            <Select
                                id="status"
                                v-model="form.status"
                                :options="opcoes.status"
                                option-label="label"
                                option-value="value"
                                class="w-full"
                                :invalid="!!form.errors.status"
                            />
                            <Message
                                v-if="form.errors.status"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.status }}
                            </Message>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="admissao" class="text-[12.75px] font-semibold text-ink-90">
                                Data de admissão <span class="text-laranja-600">●</span>
                            </label>
                            <DatePicker
                                id="admissao"
                                v-model="form.data_admissao"
                                date-format="dd/mm/yy"
                                show-icon
                                icon-display="input"
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

                        <div class="flex flex-col gap-1.5">
                            <label for="salario" class="text-[12.75px] font-semibold text-ink-90">
                                Salário base <span class="text-laranja-600">●</span>
                            </label>
                            <InputNumber
                                id="salario"
                                v-model="form.salario_base"
                                mode="currency"
                                currency="BRL"
                                locale="pt-BR"
                                :min="0"
                                class="w-full"
                                input-class="mono"
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

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label
                                for="observacoes"
                                class="text-[12.75px] font-semibold text-ink-90"
                            >
                                Observações
                            </label>
                            <Textarea
                                id="observacoes"
                                v-model="form.observacoes"
                                rows="3"
                                maxlength="5000"
                                class="w-full"
                            />
                            <Message
                                v-if="form.errors.observacoes"
                                severity="error"
                                size="small"
                                variant="simple"
                            >
                                {{ form.errors.observacoes }}
                            </Message>
                        </div>
                    </div>

                    <Aviso v-if="vaiDesligar" tom="atencao" icone="alertTriangle" class="mt-4">
                        Marcar <strong>Desligado</strong> aqui bloqueia novos lançamentos de folha,
                        mas não registra a data de desligamento. Para isso, use a ação
                        <strong>Desligar</strong> na tela do colaborador.
                    </Aviso>
                </div>

                <!-- Ações -->
                <div class="rounded-lg border border-ink-8 bg-white shadow-card">
                    <div class="flex justify-end gap-2.5 px-5 py-4">
                        <Link :href="route('colaboradores.index')">
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
                            :label="editando ? 'Salvar alterações' : 'Cadastrar'"
                            size="small"
                            :loading="form.processing"
                            :disabled="cpfInvalido"
                        >
                            <template #icon><Icone nome="checkCircle" :tamanho="16" /></template>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
