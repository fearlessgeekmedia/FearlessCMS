import { createHooks } from "atomico/test-hooks";
import { expect, it } from "vitest";
import { redirect, useRouter } from "../src/";

it("useRouter", async () => {
	const hooks = createHooks(
		() => {
			hooks.load(load);
		},
		{ updated: Promise.resolve() },
	);

	const router = [];

	function load() {
		const route = useRouter({
			"/": () => "home",
			"/config": () => "config",
		});

		router.push(route.result);
	}

	hooks.load(load);

	hooks.cleanEffects()()();

	await new Promise((resolve) => setTimeout(resolve, 100));

	redirect("/config");

	await new Promise((resolve) => setTimeout(resolve, 100));

	expect(router).to.deep.equal(["home", "config"]);
});
