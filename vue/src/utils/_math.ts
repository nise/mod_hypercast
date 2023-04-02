export const clamp = (num, min, max): number => Math.min(Math.max(num,min), max);
export const isBetween = (num, min, max, included=false): boolean => included ? num >= min && num <= max : num > min && num < max;
export const percentageInputBetweenRange = (input: number, min: number, max: number): number => ((input - min) * 100) / (max - min);

export const getOffset = (el) => {
    const rect = el.getBoundingClientRect();
    return {
        id: el.getAttribute('data-user-id'),
        top: rect.top + window.scrollY,
        height: el.clientHeight
    };
}

export function checkCollision(values: Array<{ id: string, top: number, height: number }>) {
    const collisions: Array<{ first: string, second: string }> = [];
    for (let i = 0; i < values.length; i++) {
        for (let j = i + 1; j < values.length; j++) {
            const collision = isCollided(values[i], values[j])
            if (collision !== null) {
                collisions.push(collision)
            }
        }
    }
    return collisions
}

function isCollided(v1: { id: string, top: number, height: number }, v2: { id: string, top: number, height: number }) {
    let top;
    let bottom;
    if (v1.top > v2.top) {
        top = v2;
        bottom = v1;
    } else {
        top = v1;
        bottom = v2;
    }
    if (bottom.top < (top.top + top.height)) {
        return {first: top.id, second: bottom.id};
    } else {
        return null;
    }
}
