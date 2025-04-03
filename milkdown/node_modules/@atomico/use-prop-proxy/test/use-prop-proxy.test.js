import { createHooks } from "atomico/test-hooks";
import { describe, expect, it } from "vitest";
import { usePropProxy } from "../src";

describe("usePropProxy", () => {
	it("case 1", () => {
		const host = document.createElement("input");
		const hooks = createHooks(null, host);

		hooks.load(() => {
			usePropProxy("value", {
				set() {},
				get() {
					return "proxy";
				},
			});
		});

		expect(host.value).to.equal("proxy");
	});

	it("case 2", () => {
		const host = document.createElement("input");
		const hooks = createHooks(null, host);
		const log = [];

		hooks.load(() => {
			usePropProxy("value", {
				set(value) {
					log.push(value);
				},
			});
		});

		host.value = "1";
		host.value = "2";
		host.value = "3";

		expect(log).to.deep.equal(["1", "2", "3"]);

		expect(host.value).to.equal("3");
	});
});
