/**
 * Debounce enxuto para campos de busca, evitando puxar lodash so por isto.
 */
export function debounce<T extends (...args: never[]) => void>(
    fn: T,
    espera = 300,
): (...args: Parameters<T>) => void {
    let timer: ReturnType<typeof setTimeout> | undefined;

    return (...args: Parameters<T>) => {
        if (timer) clearTimeout(timer);

        timer = setTimeout(() => fn(...args), espera);
    };
}
