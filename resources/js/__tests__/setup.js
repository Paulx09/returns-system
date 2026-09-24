import '@testing-library/jest-dom/vitest';
import { expect } from 'vitest';
import * as matchers from 'vitest-axe/matchers';

expect.extend(matchers);

if (typeof window !== 'undefined' && globalThis.HTMLCanvasElement) {
    globalThis.HTMLCanvasElement.prototype.getContext = () => null;
}

globalThis.route = (name) => `/route/${name}`;