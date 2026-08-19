<script setup lang="ts">
import Icone from '@/Components/Icone.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useFormato } from '@/Composables/useFormato';
import type { HistoricoStatus } from '@/types';

/**
 * Trilha de auditoria de mudança de status.
 *
 * A tabela historico_status é polimórfica e serve a pagamentos e reembolsos, então
 * este componente não conhece nenhum dos dois: recebe os eventos e desenha.
 *
 * Eventos sem usuário vieram das rotinas agendadas (06:00 gera os recorrentes,
 * 06:15 promove os vencidos), porque não há ninguém autenticado no cron. É isso
 * que distingue o automático do manual — marcador tracejado e etiqueta própria.
 */
defineProps<{
    eventos: HistoricoStatus[];
}>();

const { formatarDataHora } = useFormato();

const ehAutomatico = (evento: HistoricoStatus) => evento.usuario === null;
</script>

<template>
    <div v-if="eventos.length" class="relative pl-[26px]">
        <!-- Fio que liga os marcadores -->
        <span
            class="absolute bottom-1.5 left-[9px] top-1.5 w-[1.5px] bg-ink-16"
            aria-hidden="true"
        />

        <ol class="list-none space-y-5 p-0">
            <li v-for="evento in eventos" :key="evento.id" class="relative">
                <span
                    class="absolute -left-[26px] top-0.5 flex h-[19px] w-[19px] items-center justify-center rounded-full border-2 bg-white"
                    :class="ehAutomatico(evento) ? 'border-dashed border-ink-35' : 'border-ink-16'"
                    aria-hidden="true"
                >
                    <Icone nome="chevronRight" :tamanho="11" class="text-ink-55" />
                </span>

                <div class="flex flex-wrap items-center gap-2 text-[13px] font-semibold">
                    <template v-if="evento.status_anterior">
                        <StatusBadge :status="evento.status_anterior" tamanho="sm" />
                        <Icone nome="chevronRight" :tamanho="12" class="text-ink-35" />
                    </template>
                    <StatusBadge :status="evento.status_novo" tamanho="sm" />

                    <span
                        v-if="ehAutomatico(evento)"
                        class="rounded-full bg-azul-50 px-[7px] py-px text-[10.5px] font-semibold text-azul-600"
                    >
                        Automático
                    </span>
                </div>

                <p class="mt-0.5 text-[11.75px] text-ink-55">
                    {{ evento.usuario?.name ?? 'Rotina do sistema' }} ·
                    <span class="mono">{{ formatarDataHora(evento.created_at) }}</span>
                </p>

                <p
                    v-if="evento.observacao"
                    class="mt-1.5 rounded-[7px] bg-ink-8 px-2.5 py-[7px] text-[12.5px] text-ink-70"
                >
                    {{ evento.observacao }}
                </p>
            </li>
        </ol>
    </div>

    <p v-else class="py-6 text-center text-[12.75px] text-ink-55">
        Nenhuma mudança de status registrada.
    </p>
</template>
