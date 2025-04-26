import atomico from "@atomico/vite";
import { defineConfig } from "vite";

export default defineConfig({
	build: {
		target: "esnext",
	},
	test: {
		environment: "happy-dom",
	},
	plugins: [
		...atomico({
			cssLiterals: { postcss: true },
			vitest: true,
		}),
	],
});
