export function reactOnDrawerChange(element: HTMLElement, className = 'drawer-open'): (() => void) | undefined {
    // Get the button which toggles the sidedrawer.
    const toggleDrawerButton = document.querySelector(
        'button[data-action="toggle-drawer"]'
    );

    if (!toggleDrawerButton || !element) return;

    // if we find a body component (which has a class of drawer-open-left), we initially set our class to 'drawer-open'.
    const body = document.querySelector('body.drawer-open-left');
    if (body) element.classList.add(className);

    // Add a click event function to the drawer-open-button which toggles the 'drawer-open' class on our playeroverlay root div.
    const clickEvent = () => element.classList.toggle(className);
    toggleDrawerButton.addEventListener('click', clickEvent);

    // return the removeEventListener for possible unsubscribe from click-event
    return () => toggleDrawerButton.removeEventListener('click', clickEvent);
}