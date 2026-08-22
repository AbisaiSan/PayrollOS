export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    colaborador_id?: number | null;
    ativo?: boolean;
}

/** Item de select vindo dos enums PHP (App\Enums\Concerns\TemRotulo::opcoes). */
export interface Opcao {
    value: string;
    label: string;
}

/** Estrutura de paginacao do Laravel usada pelo DataTable server-side. */
export interface Paginado<T> {
    data: T[];
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export type StatusPagamento =
    | 'pendente'
    | 'agendado'
    | 'pago'
    | 'atrasado'
    | 'cancelado';

export type StatusReembolso = 'pendente' | 'aprovado' | 'pago' | 'rejeitado';

export interface ContaBancaria {
    id: number;
    banco: string;
    codigo_banco: string | null;
    agencia: string;
    conta: string;
    digito: string | null;
    tipo_conta: 'corrente' | 'poupanca';
    titular_nome: string;
    titular_documento: string;
    chave_pix: string | null;
    tipo_chave_pix: string | null;
    principal: boolean;
    status: 'ativa' | 'inativa';
    resumo?: string;
}

export interface Colaborador {
    id: number;
    nome: string;
    cpf: string;
    cargo: string;
    departamento: string;
    tipo_contrato: string;
    data_admissao: string;
    data_desligamento: string | null;
    salario_base: string;
    email: string | null;
    telefone: string | null;
    status: 'ativo' | 'afastado' | 'desligado';
    observacoes?: string | null;
    contas_bancarias?: ContaBancaria[];
}

export interface Fornecedor {
    id: number;
    tipo_pessoa: 'pf' | 'pj';
    razao_social: string;
    nome_fantasia: string | null;
    documento: string;
    tipo_fornecedor: 'produto' | 'servico' | 'ambos';
    email: string | null;
    telefone: string | null;
    endereco: string | null;
    observacoes?: string | null;
    status: 'ativo' | 'inativo';
    contas_bancarias?: ContaBancaria[];
}

export interface CategoriaPagamento {
    id: number;
    nome: string;
    tipo: string;
    descricao?: string | null;
    ativo: boolean;
}

export interface Pagamento {
    id: number;
    payable_type: string;
    payable_id: number;
    beneficiario_nome?: string;
    categoria_id: number;
    categoria?: CategoriaPagamento;
    contrato_id: number | null;
    conta_bancaria_id: number | null;
    competencia: string | null;
    descricao: string;
    valor: string;
    data_vencimento: string;
    data_pagamento: string | null;
    forma_pagamento: string;
    status: StatusPagamento;
    observacoes: string | null;
}

export interface Reembolso {
    id: number;
    colaborador_id: number;
    descricao: string;
    categoria: string;
    valor: string;
    data_solicitacao: string;
    data_pagamento: string | null;
    status: StatusReembolso;
}

export interface HistoricoStatus {
    id: number;
    status_anterior: string | null;
    status_novo: string;
    observacao: string | null;
    created_at: string;
    /** Nulo quando a mudanca veio de uma rotina agendada, sem usuario autenticado. */
    usuario?: { id: number; name: string } | null;
}

export interface Anexo {
    id: number;
    nome_arquivo: string;
    tipo_arquivo: string | null;
    tamanho: number;
    created_at: string;
    enviado_por?: { id: number; name: string } | null;
}

/** Item do menu lateral, montado por App\Support\Navegacao. */
export interface ItemNavegacao {
    rotulo: string;
    rota: string;
    icone: string;
    permissao: string | null;
}

export interface GrupoNavegacao {
    titulo: string;
    itens: ItemNavegacao[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        /** Permissoes efetivas do usuario, para esconder acoes na UI. */
        permissoes: string[];
        perfis: string[];
        perfilRotulo: string | null;
    };
    /** Menu ja filtrado pelo backend conforme as permissoes do usuario. */
    navegacao: GrupoNavegacao[];
    flash: {
        sucesso?: string;
        erro?: string;
    };
};
