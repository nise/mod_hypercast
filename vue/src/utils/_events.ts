/**
 * @description Use a specified function on clicking anywhere except on the list of defined excluded HTML Elements.
 * @param excludedElements The list of elements which should not trigger a function call of the provided function.
 * @param handler The function which should be called if the clicked element has not been excluded.
 */
export const registerClickaway = (excludedElements: HTMLElement[], handler: () => void) => {
    document.addEventListener('mousedown', (e) => {
        if (!e.target || !(e.target instanceof Element)) return

        for (const excludedElement of excludedElements) {
            if (excludedElement.contains(e.target)) return
        }

        handler()
    })
}