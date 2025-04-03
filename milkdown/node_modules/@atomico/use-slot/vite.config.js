import atomico from "@atomico/vite";
import { defineConfig } from "vite";

export default defineConfig({
	build: {
		target: "esnext",
	},
	test: {
		browser: {
			enabled: true,
			headless: true,
			name: "chrome", // browser name is required
		},
	},
	plugins: [
		...atomico({
			cssLiterals: { postcss: true },
			vitest: true,
		}),
	],
});
