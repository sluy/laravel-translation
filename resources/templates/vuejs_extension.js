import Vue from "vue";
import LaravelTranslation from '{translation_lib_path}';

/**
 * VueJS LaravelTranslation extension configuration.
 */
const config = {
  install(Vue) {
    Vue.prototype.$laravelTranslation = LaravelTranslation;
    Vue.prototype.__ = (key, replaces, locales) =>
      Vue.prototype.$laravelTranslation.translate(key, replaces, locales);
    Vue.directive("trans", {
      bind: function (el, binding) {
        let key = el.innerHTML;
        if (
          typeof binding.value === "string" &&
          binding.value.trim().length > 0
        ) {
          key = binding.value.trim();
        }
        el.innerHTML = Vue.prototype.$laravelTranslation.translate(key);
      }
    });
  },
  /**
   * Injects configuration to VueJS.
   */
  inject() {
    Vue.use(this);
  }
};

export default config;