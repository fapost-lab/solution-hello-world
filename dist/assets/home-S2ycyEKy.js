import { createElementBlock as e, createElementVNode as t, createTextVNode as n, createVNode as r, defineComponent as i, openBlock as a, ref as o, toDisplayString as s, unref as c, withCtx as l } from "vue";
import { Head as u } from "@inertiajs/vue3";
import { Button as d } from "@fapost/ui/components/button";
//#region resources/js/pages/Home.vue?vue&type=script&setup=true&lang.ts
var f = { class: "hello-home" }, p = { class: "hello-home__title" }, m = /* @__PURE__ */ i({
	__name: "Home",
	props: {
		title: {},
		greeting: {}
	},
	setup(i) {
		let m = i, h = o(0);
		return (i, o) => (a(), e("section", f, [
			r(c(u), { title: m.title }, null, 8, ["title"]),
			t("h1", p, s(m.greeting), 1),
			r(c(d), { onClick: o[0] ||= (e) => h.value++ }, {
				default: l(() => [n("Clicked " + s(h.value), 1)]),
				_: 1
			})
		]));
	}
});
//#endregion
export { m as default };
