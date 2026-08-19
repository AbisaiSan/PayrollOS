import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';

/**
 * Esconde acoes que o usuario nao pode executar.
 *
 * Isto e conveniencia de UI, nao seguranca: a policy no backend continua sendo
 * a autoridade em toda rota.
 */
export function usePermissoes() {
    const page = usePage<PageProps>();

    const permissoes = computed(() => page.props.auth?.permissoes ?? []);
    const perfis = computed(() => page.props.auth?.perfis ?? []);

    const pode = (permissao: string) => permissoes.value.includes(permissao);

    const podeAlguma = (lista: string[]) =>
        lista.some((permissao) => permissoes.value.includes(permissao));

    const ehAdministrador = computed(() => perfis.value.includes('administrador'));

    return { permissoes, perfis, pode, podeAlguma, ehAdministrador };
}
