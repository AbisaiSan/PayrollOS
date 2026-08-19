<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Geracao de lancamentos recorrentes
    |--------------------------------------------------------------------------
    |
    | Quantos dias antes do vencimento a rotina agendada cria o pagamento a partir
    | de um contrato recorrente (regra 3.6). O lancamento nasce como Pendente.
    |
    */

    'antecedencia_geracao_dias' => env('PAYROLLOS_ANTECEDENCIA_GERACAO_DIAS', 5),

    /*
    |--------------------------------------------------------------------------
    | Identidade visual Corebanx
    |--------------------------------------------------------------------------
    |
    | Espelha as cores usadas no tailwind.config.js. Fica aqui para uso no backend
    | (PDFs e planilhas exportadas), que nao passa pelo Tailwind.
    |
    */

    'marca' => [
        'laranja' => '#F37B46',
        'azul' => '#214396',
        'preto' => '#0D0E0E',
        'cinza' => '#EFEFEE',
    ],

    /*
    |--------------------------------------------------------------------------
    | Anexos
    |--------------------------------------------------------------------------
    */

    'anexos' => [
        'tamanho_maximo_kb' => env('PAYROLLOS_ANEXO_MAX_KB', 10240),
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'xml'],
    ],

];
