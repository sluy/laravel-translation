/**
 * Module to translations management.
 * 
 * @author Stefan Luy <sluy1283@gmail.com>
 */
export default class LaravelTranslation {
  /**
   * Constructor.
   * @param {string, array} locales Locales to set in instance. If not setted, it will
   *                                resolves automatically from global `languages` variable. 
   */
  constructor(locales) {
    globalConfig.instances.push(this);
    this._id = globalConfig.instances.length;
    this._locales = [];
    this._translations = Object.create(null);
    this._translationResolver = null;
    if (locales) {
      this.setLocale(locales);
    } else {
      this.setLocale(LaravelTranslation.getClientLocales());
    }
  }

  /**
   * Resolves translation importations.
   * @param   {string} path Relative path of search.
   * @returns {object} 
   */
  resolveImport(path) {
    try {
      // base location autogenerated
      const tmp = require(`{js_collection_path}/${path}`);
      if (typeof tmp === 'object' && tmp !== null && typeof tmp.default === 'object' && typeof tmp.default !== null) {
        return tmp.default;
      }
    } catch (error) {

    }
    return Object.create(null);
  }

  /**
   * Sets the current instance as default `LaravelTranslation` instance.
   * @return {this}
   */
  setDefault() {
    LaravelTranslation.setDefault(this._id);
    return this;
  }
  /**
   * Returns the first locale in current instance.
   * @returns string
   */
  getLocale() {
    return this._locales[0];
  }
  /**
   * Returns all locales in current instance.
   * @return {Array}
   */
  getLocales() {
    return this._locales;
  }
  /**
   * Sets current instance locales.
   * @param {string|array} locales An string with locales separated by comma character or 
   *                               an array with locale as value.
   * @returns {this} 
   */
  setLocale(locales) {
    this._locales = LaravelTranslation.formatLocales(locales, ['en']);
    if (this._locales.length < 1) {
      this._locales.push('en');
    }
    return this;
  }

  /**
   * Returns value of specific key.
   * @param {string} key 
   * @param {string|array} locales
   * @return {*|null} 
   */
  getValue(key, locales) {
    let pkg = null;
    let group = null;

    if (typeof key !== 'string' || key.trim().length < 1) {
      return null;
    }
    key = key.trim();
    if (key.indexOf('::') !== -1) {
      [pkg, key] = key.split('::');
    }
    const sec = key.split('.');
    //must be group.key
    if (sec.length < 2) {
      return null;
    }
    [group, key] = sec;
    for (let locale of LaravelTranslation.formatLocales(locales, this._locales)) {
      let base = null;
      let path = null;
      //Located in package
      if (pkg) {
        //creating [vendor]
        if (typeof this._translations['vendor'] !== 'object' || this._translations['vendor'] === null) {
          this._translations['vendor'] = Object.create(null);
        }
        const v = this._translations['vendor'];
        //creating [vendor][package]
        if (v[pkg] !== 'object' || v[pkg] === null) {
          v[pkg] = Object.create(null);
        }
        base = v[pkg];
        path = `vendor/${pkg}/${locale}/${group}`;
      }
      //Located in common
      else {
        //creating [common]
        if (typeof this._translations['common'] !== 'object' || this._translations['common'] === null) {
          this._translations['common'] = Object.create(null);
        }
        base = this._translations['common'];
        path = `${locale}/${group}`;
      }
      //creating base[locale]
      if (base[locale] !== 'object' || base[locale] === null) {
        base[locale] = Object.create(null);
      }

      const l = base[locale];
      //creating base[locale][group] -> words
      if (l[group] !== 'object' || l[group] === null) {
        l[group] = this.resolveImport(path);
      }
      const g = l[group];
      try {
        //Looking recursively.
        let value = key.split('.').reduce((t, i) => t[i] || null, g);
        if (value) {
          return value;
        }
      } catch (error) {

      }
    }
    //not found
    return null;
  }
  /**
   * Resolve appropiated translation for provided key.
   * @param {string} key Keyword to search. 
   * @param {Object} replaces Arguments to replace in translation. 
   * @param {string|array} locales String with locales separated by comma character or
   *                               an array with locale as value. If not defined, it will
   *                               takes instance locales.
   * @return {string}
   */
  translate(key, replaces, locales) {
    let tmp = this.getValue(key, locales);
    if (typeof tmp !== 'string') {
      return key;
    }
    if (typeof replaces === 'object' && replaces !== null) {
      for (const k of Object.keys(replaces)) {
        if (['string', 'number', 'boolean'].indexOf(typeof replaces[k]) !== -1) {
          const search = ':' + k;
          const replace = replaces[k];
          while (1) {
            if (tmp.indexOf(replace) === -1) {
              break;
            }
            tmp = tmp.replace(search, replace);
          }
        }
      }
    }
    return tmp;
  }


  /**
   * Sets the default instance.
   * @return {this}
   */
  static setDefault(id) {
    if (typeof id === 'number' && globalConfig.instances[id] !== undefined) {
      globalConfig.default = id;
    }
    return this;
  }
  /**
   * Clear default instance.
   * @return {this}
   */
  static clearDefault() {
    globalConfig.default = null;
    return this;
  }
  /**
   * Gets the default instance.
   * If isnt defined it will returns the first Module instance.
   * @return {LaravelTranslation}
   */
  static getDefault() {
    if (globalConfig.instances.length < 1) {
      new LaravelTranslation();
    }
    return globalConfig.default !== null
      ? this._instances[globalConfig.default]
      : globalConfig.instances[0];
  }
  /**
   * Resolve locales directly from navigator.
   * @return {Array}
   */
  static getClientLocales() {
    let raw = [];
    if (Array.isArray(navigator.languages)) {
      raw = navigator.languages;
    } else {
      raw = [navigator.language];
    }
    return raw.map((tmp) => {
      return tmp.toLowerCase();
    });
  }
  /**
   * Normalizes provided locales to array format.
   * @param {string|array} locales      Locales to format.
   * @param {array}        defaultValue Default locales to return if normalization results
   *                                    returns an empty array.
   * @return {array}
   */
  static formatLocales(locales, defaultValue) {
    if (typeof locales === "string") {
      locales = locales.split(",");
    }
    return Array.isArray(locales)
      ? locales
        .map(raw =>
          typeof raw === "string" ? raw.trim().toLowerCase() : ""
        )
        .filter(value => value.length > 0)
      : (Array.isArray(defaultValue) ? defaultValue : []);
  }
  /**
   * Resolve appropiated translation for provided key.
   * It will uses the default instance with `LaravelTranslation.getDefault()` method.
   * @param {string}       key Keyword to search.
   * @param {Object}       replaces Arguments to replace in translation.
   * @param {string|array} locales String with locales separated by comma character or
   *                               an array with locale as value. If not defined, it will
   *                               takes instance locales.
   * @return {string}
   */
  static translate(key, replaces, locales) {
    return this.getDefault().translate(key, replaces, locales);
  }
  /**
   * Inject  classes and methods to window global.
   * @returns {LaravelTranslation}
   */
  static injectGlobal() {
    window.LaravelTranslation = LaravelTranslation;
    window.__ = (key, replaces, locales) => LaravelTranslation.translate(key, replaces, locales);
    return this;
  }
}
/**
 * Diverse configuration.
 */
const globalConfig = {
  instances: [], // Initialized instances
  default: null  //key of default instance
};