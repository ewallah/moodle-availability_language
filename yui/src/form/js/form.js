/**
 * JavaScript for form editing language conditions.
 *
 * @module moodle-availability_language-form
 */
M.availability_language = M.availability_language || {};

/**
 * @class M.availability_language.form
 * @extends M.core_availability.plugin
 */
M.availability_language.form = Y.Object(M.core_availability.plugin);

/**
 * Languages available for selection.
 *
 * @property languages
 * @type Array
 */
M.availability_language.form.languages = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} languages Array of objects containing languageid => name
 */
M.availability_language.form.initInner = function(languages) {
    this.languages = languages;
};

M.availability_language.form.getNode = function(json) {
    // Create HTML structure.
    var strings = M.str.availability_language;
    var html = '<label>' + strings.title + ' <span class="availability-language">' +
            '<select name="id">' +
            '<option value="choose">' + M.str.moodle.choosedots + '</option>';
    for (var i = 0; i < this.languages.length; i++) {
        var language = this.languages[i];
        // String has already been escaped using format_string.
        html += '<option value="' + language.id + '">' + language.name + '</option>';
    }
    html += '</select></span></label>';
    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values (leave default 'choose' if creating afresh).
    if (json.creating === undefined) {
        if (json.id !== undefined && node.one('select[name=id] > option[value=' + json.id + ']')) {
            node.one('select[name=id]').set('value', json.id);
        } else if (json.id === undefined) {
            node.one('select[name=id]').set('value', 'choose');
        }
    }

    // Add event handlers (first time only).
    if (!M.availability_language.form.addedEvents) {
        M.availability_language.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            // Just update the form fields.
            M.core_availability.form.update();
        }, '.availability_language select');
    }

    return node;
};

M.availability_language.form.fillValue = function(value, node) {
    var selected = node.one('select[name=id]').get('value');
    if (selected === 'choose') {
        value.id = '';
    } else {
        value.id = selected;
    }
};

M.availability_language.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check language item id.
    if (value.id === '') {
        errors.push('availability_language:missing');
    }

};
