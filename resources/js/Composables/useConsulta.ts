import { onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { RequestPayload } from '@inertiajs/core';

/**
 * Consulta de listagem com os estados que a tarefa 40 define.
 *
 * Toda listagem faz o mesmo router.get com preserveState; o que faltava era
 * saber quando ela esta no ar (esqueleto) e quando a ida ao servidor falhou
 * (erro de carregamento, com acao de tentar de novo).
 *
 * `inertia:exception` e o evento de falha de rede. Erro de validacao nao passa
 * por aqui — vai para form.errors, que e outra conversa.
 */
export function useConsulta(rota: string) {
    const carregando = ref(false);
    const erro = ref(false);

    let ultimosParametros: RequestPayload = {};
    let visitaEmAndamento = false;

    const aoFalhar = () => {
        if (visitaEmAndamento) {
            erro.value = true;
        }
    };

    const remover = router.on('exception', aoFalhar);

    onUnmounted(() => remover());

    const consultar = (parametros: RequestPayload = {}) => {
        ultimosParametros = parametros;

        router.get(rota, parametros, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                visitaEmAndamento = true;
                carregando.value = true;
                erro.value = false;
            },
            onFinish: () => {
                visitaEmAndamento = false;
                carregando.value = false;
            },
        });
    };

    const tentarNovamente = () => consultar(ultimosParametros);

    return { carregando, erro, consultar, tentarNovamente };
}
