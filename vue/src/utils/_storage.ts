export const storageGet = key => {
    return localStorage.getItem('mod_hypercast/' + key);
}

export const storageSet = (key, value) => {
    localStorage.setItem('mod_hypercast/' + key, JSON.stringify(value));
}