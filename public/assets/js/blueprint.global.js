const Cookie = {
    get(name) {
        var i, x, y, ARRcookies = document.cookie.split(";");
        for (i = 0; i < ARRcookies.length; i++) {
            x = ARRcookies[i].substr(0, ARRcookies[i].indexOf("="));
            y = ARRcookies[i].substr(ARRcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g, "");
            if (x == name) {
                return y ? decodeURI(unescape(y.replace(/\+/g, ' '))) : y; //;//unescape(decodeURI(y));
            }
        }
        return "";
    }
};

const CloseModalHandler = {
    checkClicked(modal_name, event) {
        if (event != undefined) {
            event.stopPropagation();
            if(event.key != undefined) {
                if(event.key != 'Escape') // not escape
                    return true;
            }
            else {
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if (clicked_element.id != "x-button" && !(clicked_element.classList.contains("dmiux_popup__cancel")))
                        return true;
                }
                else if(clicked_element.closest(".notyf") || clicked_element.closest("#loading")) {
                    return true;
                }
                else {
                    if(clicked_element.id == modal_name)
                        return true;
                }
            }
        }

        return false;
    }
}

const BytespreeUiHelper = {
    hideShowArrows(element_name, arrow_name) {
        var element = document.getElementById(element_name);

        if(element != null) {
            var scrollWidth = element.scrollWidth;
            var scrollLeft = element.scrollLeft;
            var outerWidth = $("#" + element_name).outerWidth();

            if(scrollWidth - scrollLeft == outerWidth && scrollLeft == 0) {
                $("." + arrow_name + "right").removeClass("dmiux_data-table__arrow_visible");
                $("." + arrow_name + "left").removeClass("dmiux_data-table__arrow_visible");
                return;
            }

            if(scrollWidth - scrollLeft == outerWidth) {
                $("." + arrow_name + "right").removeClass("dmiux_data-table__arrow_visible");
            }
            else if (scrollLeft == 0) {
                $("." + arrow_name + "left").removeClass("dmiux_data-table__arrow_visible");
                $("." + arrow_name + "right").addClass("dmiux_data-table__arrow_visible");
            }
            else {
                $("." + arrow_name + "left").addClass("dmiux_data-table__arrow_visible");
                $("." + arrow_name + "right").addClass("dmiux_data-table__arrow_visible");
            }
        }
    },
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
    
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    
        const i = Math.floor(Math.log(bytes) / Math.log(k));
    
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
}

function FetchException(response, json) {
    this.response = response;
    this.json = json;
    this.name = "FetchException";
}

const FetchHelper = {
    handleJsonResponse: async function (response) {
        var json = null;
        try {
            json = await response.json();
            json = ResponseHelper.validateJson(json);
        }
        catch {
            // parsing failed
        }
        // response.ok returns TRUE if HTTP status code is between 200-299
        return new Promise((resolve, reject) => {
            if (!response.ok || !json) {
                reject(new FetchException(response, json));
            }
            else {
                resolve(json);
            }
        });
    },
    buildJsonRequest: function (params, method = 'post') {
        return {
            method: method,
            headers: {
                'Content-type': 'application/json; charset=UTF-8'
            },
            body: JSON.stringify(
                params
            )
        };
    }
};

const ResponseHelper = {
    validateJson(json) {
        if (!json.status) {
            console.warn("The 'status' property was missing in JSON body");
            json.status = "error";
        }

        if (!json.message) {
            console.warn("The 'message' property was missing in JSON body");
            json.message = "";
        }

        return json;
    },
    handleErrorMessage(error, defaultMessage) {
        if (error.json) {
            if (error.json.message && error.json.message !== "") {
                notify.danger(error.json.message, true);
                return;
            }

            if (error.json.data.errors != undefined) {
                for (e in error.json.data.errors) {
                    notify.danger(error.json.data.errors[e][0], true);
                }
                return;
            }
        }
        
        notify.danger(defaultMessage, true);
    }
};

const StringHelper = {
    isEmpty(value) {
        if (!value) {
            return true;
        }
        else {
            if (typeof value != "string")
                value = value.toString();

            if (value.length == 0 || value.trim() === "" ) {
                return true;
            }
            else {
                return false;
            }
        }
    },
    pluralize(count, noun, suffix = 's') {
        return `${noun}${count !== 1 ? suffix : ''}`
    }
};

const notyfHelper = {
    showUrlMessage() {
        url = new URL(window.location.href);
        if (url.searchParams.get('message')) {
            if (url.searchParams.get('message_type')) {
                var msg_type = url.searchParams.get('message_type');
                if (msg_type == "success") {
                    notify.success(url.searchParams.get('message'));
                }
                else if (msg_type == "info") {
                    notify.info(url.searchParams.get('message'));
                }
                else if (msg_type == "danger") {
                    notify.danger(url.searchParams.get('message'));
                }
            }
            else {
                notify.info(url.searchParams.get('message'));
            }
            
            url.searchParams.delete('message');
            url.searchParams.delete('message_type');
            window.history.replaceState(
                {},
                '',
                `${window.location.pathname}?${url.searchParams}${window.location.hash}`
            );
        }
    }
};

const paramsHelper = {
    clearOAuth() {
        document.cookie = "bytespree_state=; expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;domain=bytespree.com";
        url.searchParams.delete('code');
        url.searchParams.delete('guid');
        url.searchParams.delete('error');
        url.searchParams.delete('error_description');
        window.history.replaceState(
            {},
            '',
            `${window.location.pathname}?${url.searchParams}${window.location.hash}`
        );
    }
};

const DateHelper = {
    formatLocaleDateTimeString(date_time) {
        date_time = date_time.replace(/-/g, "/");
        var formatted_date_time = date_time.substring(0, date_time.indexOf('.')) + ' UTC';
        return new Date(formatted_date_time).toLocaleString();
    },
    formatLocaleCarbonDate(date_time) {
        return new Date(date_time).toLocaleString();
    },
    formatLocaleDateString(date_time) {
        date_time = date_time.replace(/-/g, "/");
        var formatted_date_time = date_time.substring(0, date_time.indexOf('.')) + ' UTC';
        return new Date(formatted_date_time).toLocaleDateString();
    },
    convertToAndFormatLocaleDateTimeString(date_time) {
        date_time = date_time.replace(/-/g, "/");
        var local_timestamp = new Date(date_time);
        return local_timestamp.toLocaleString();
    },
    formatFromUtc(date_time) {
        return (new Date(date_time + ' UTC')).toLocaleString();
    },
    buildDateObjectForTime(time) {
        if(time == null || time == undefined) {
            return "";
        }

        time = time.toString().match (/^([01]\d|2[0-3])(:)([0-5]\d)((:)([0-5]\d))?((\+|\-)(\d\d))?$/);
        if (time == null) {
            return "";
        }

        var today = new Date();
        var year = today.getFullYear();
        var month = today.getMonth() + 1;
        var date = today.getDate();
        var hour = time[1];
        var minutes = time[3];
        var seconds = "00"
        var tz_offset = "+"
        var tz_offset_value = "00";
        month = month.toString().padStart(2, '0');
        date = date.toString().padStart(2, '0');

        if (time.length > 3 && time[6] != undefined) {
            seconds = time[6];
        }

        if (time.length > 6) {
            tz_offset = time[8];
            tz_offset_value = time[9];
        }

        var datestring = year + '-' + month + '-' + date + 'T' + hour + ':' + minutes + ':' + seconds + '.000' + tz_offset + tz_offset_value + ':00';
        return new Date(datestring);
    },
    formatUtcTimeString(time) {
        var timeString = "";
        var date = this.buildDateObjectForTime(time);
        if (date instanceof Date) {
            let utcDate = new Date(
                date.getUTCFullYear(),
                date.getUTCMonth(),
                date.getUTCDate(),
                date.getUTCHours(),
                date.getUTCMinutes(),
                date.getUTCSeconds()
            );
            var timeString = utcDate.toLocaleTimeString();
            if (timeString == "Invalid Date") {
                console.error("Invalid date detected", date);
                timeString = "";
            }       
        }

        return timeString;
    },
    formatLocaleTimeString(time) {
        var timeString = "";
        var date = this.buildDateObjectForTime(time);
        if (date instanceof Date) {
            timeString = date.toLocaleTimeString();
            if (timeString == "Invalid Date") {
                console.error("Invalid date detected", date);
                timeString = "";
            }
        }

        return timeString;
    },
    getUTCFormattedDate(date = null) {
        if (!date)
            date = new Date();
        let utcDate = new Date(
            date.getUTCFullYear(),
            date.getUTCMonth(),
            date.getUTCDate(),
            date.getUTCHours(),
            date.getUTCMinutes(),
            date.getUTCSeconds()
        );
        return utcDate.toLocaleString();
    }
}

const formatLocaleDateTimeString = DateHelper.formatLocaleDateTimeString;
const formatLocaleDateString = DateHelper.formatLocaleDateString;

const ConditionParser = {
    debug : false,
    evaluate(expression, variables, onErrorValue = null) {
        variables = this.prepareVariables(variables);
        for(const variable in variables) {
            let string = "var " + variable + " = " + variables[variable];
            try {
                eval (string);
            }
            catch (e) {
                if (this.debug) console.error("Could not evaluate variable: " + string);
            }
        }
        expression = this.escapeExpression(expression);
        try {
            var result = eval(expression);
            return result;
        }
        catch (e) {
            if (this.debug) console.error("Could not evaluate expression: " + expression);
            return onErrorValue;
        }
    },
    prepareVariables(variables) {
        for(const variable in variables) {
            variables[variable] = this.getParsableValue(variable, variables[variable]);
        }
        return variables;
    },
    getParsableValue(name, value) {
        var parseableValue = "";
        var type = typeof value;
        if (type == "object" && value === null) {
            parseableValue = "null";
        }
        else if (type == "object" || type == "undefined" || type == "symbol" || type == "function") {
            if (this.debug) console.error("Invalid type used for variable.  Variable " + name + " type was " + type);
            parseableValue = "null";
        }
        else if (type == "number" || type == "boolean" || (isNaN(value) === false && value !== "") || (type == "string" && value.match(/(true|false|null|undefined)/) !== null)) {
            parseableValue = value;
        }
        else {
            parseableValue = '"' + this.escapeString(value) + '"'
        }
        return parseableValue;
    },
    escapeString(value) {
        const escapeFind = ["\0", "\'", "\"", "\\", "\n", "\r", "\v", "\t", "\b", "\f"];
        const escapeReplace = ["\\0", "\\'", "\\\"", "\\\\", "\\n", "\\r", "\\v", "\\t", "\\b", "\\f"];
        for(var i=0; i<escapeFind.length; i++) {
            value = value.replaceAll(escapeFind[i], escapeReplace[i]);
        }
        return value;
    },
    escapeExpression(expression) {
        const escapeFind = ["\0", "\\", "\n", "\r", "\v", "\t", "\b", "\f"];
        const escapeReplace = ["\\0", "\\\\", "\\n", "\\r", "\\v", "\\t", "\\b", "\\f"];
        for(var i=0; i<escapeFind.length; i++) {
            expression = expression.replaceAll(escapeFind[i], escapeReplace[i]);
        }
        return expression;
    }
}

const ModalHelper = {
    shouldClose(event, config) {
        if (event != undefined) {
            event.stopPropagation();
            if(event.key != undefined) {
                if(event.key != 'Escape') // not escape
                    return false;
            }
            else {
                var clicked_element = event.target;
                if (clicked_element.closest(".dmiux_popup__window")) {
                    // You clicked inside the modal
                    if ((config.closeButtonId !== undefined && clicked_element.id != config.closeButtonId) && (config.cancelButtonId !== undefined && clicked_element.id != config.cancelButtonId) && (config.cancelButtonId !== undefined && ! clicked_element.classList.contains(config.cancelButtonId))) {
                        return false;
                    }
                }
                else if(event.target.closest(".tribute-container") || clicked_element.closest(".notyf") || clicked_element.closest("#loading")) {
                    return false;
                }
                else {
                    if(config.modalId !== undefined && clicked_element.id == config.modalId) {                        
                        return false;
                    }
                }
            }
        }
        // You either clicked outside the modal, or the X Button, or the Cancel Button - modal will close
        return true;
    },
    open(id, closeMethod){
        openModal('#' + id);
        $(document).off("mousedown", "#dmiux_body", autoClose);
        $(document).on("mousedown", "#dmiux_body", closeMethod);
        $(document).off('keydown', closeModalOnEscape);
        $(document).on("keydown", closeMethod);
    },
    close(id, closeMethod) {
        $(document).off("mousedown", "#dmiux_body", closeMethod);
        $(document).on("mousedown", "#dmiux_body", autoClose);
        $(document).off("keydown", closeMethod);
        $(document).on('keydown', closeModalOnEscape);
        closeModal('#' + id);
    }
};

const DatabaseHelper = {

    numericTypes : [
        'smallint',
        'integer',
        'bigint',
        'decimal',
        'numeric',
        'real',
        'double precision',
        'smallserial',
        'serial',
        'bigserial'
    ],

    integerTypes : [
        'smallint',
        'integer',
        'bigint',
        'smallserial',
        'serial',
        'bigserial'
    ],

    currencyTypes : [
        'money'
    ],

    characterTypes : [
        'character varying',
        'varchar',
        'character',
        'char',
        'text'
    ],

    binaryTypes : [
        'bytea'
    ],

    dateTypes : [
        'timestamp',
        'timestamp with time zone',
        'timestamp without time zone',
        'date'
    ],

    timeTypes : [
        'time',
        'time with time zone',
        'time without time zone',
        'interval'
    ],

    booleanTypes : [
        'boolean'
    ],

    jsonTypes : [
        'jsonb'
    ],

    isNumericColumn(columnType) {
        if (typeof columnType == 'string' && this.numericTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isIntegerColumn(columnType) {
        if (typeof columnType == 'string' && this.integerTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isCurrencyColumn(columnType) {
        if (typeof columnType == 'string' && this.currencyTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isCharacterColumn(columnType) {
        if (typeof columnType == 'string' && this.characterTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isBinaryColumn(columnType) {
        if (typeof columnType == 'string' && this.binaryTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isDateColumn(columnType) {
        if (typeof columnType == 'string' && this.dateTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isTimeColumn(columnType) {
        if (typeof columnType == 'string' && this.timeTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isBooleanColumn(columnType) {
        if (typeof columnType == 'string' && this.booleanTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    isJsonColumn(columnType) {
        if (typeof columnType == 'string' && this.jsonTypes.includes(columnType.toLowerCase())) {
            return true;
        }
        return false;
    },

    getColumnIconClasses(columnType, faPrefix = 'fas') {
        let classNames = faPrefix + ' ';
        if (this.isCurrencyColumn(columnType)) {
            return classNames + 'fa-dollar-sign'; 
        }
        if (this.isNumericColumn(columnType)) {
            return classNames + 'fa-hashtag'; 
        }
        else if (this.isCharacterColumn(columnType)) {
            return classNames + 'fa-font'; 
        }
        else if (this.isBinaryColumn(columnType)) {
            return classNames + 'fa-file'; 
        }
        else if (this.isDateColumn(columnType)) {
            return classNames + 'fa-calendar'; 
        }
        else if (this.isTimeColumn(columnType)) {
            return classNames + 'fa-clock'; 
        }
        else if (this.isBooleanColumn(columnType)) {
            return classNames + 'fa-check-square'; 
        }
        else if (this.isJsonColumn(columnType)) {
            return classNames + 'fa-code'; 
        }
        return classNames + 'fa-column'; 
    },

    getColumnDefinition(type, maxlength = null, precision = null) {
        let definition = type;
        if (this.isNumericColumn(type) && !this.isIntegerColumn(type)) {
            if (maxlength) {
                definition += `(${maxlength}`;
                if (precision) {
                    definition += `,${precision}`;
                }
                definition += ')';
            }
        }
        else if (this.isCharacterColumn(type)) {
            if (maxlength) {
                definition += `(${maxlength})`;
            }
        }
        return definition;
    }
}

function scroll_right(element_name, arrow_name) {
    var container = document.getElementById(element_name);
    sideScroll(container,'right',5,1000,10,element_name,arrow_name);
}

function scroll_left(element_name, arrow_name) {
    var container = document.getElementById(element_name);
    sideScroll(container,'left',5,1000,10,element_name,arrow_name);
}

function sideScroll(element,direction,speed,distance,step,element_name,arrow_name){
    scrollAmount = 0;
    var slideTimer = setInterval(function(){
        if(direction == 'left'){
            element.scrollLeft -= step;
        } else {
            element.scrollLeft += step;
        }
        scrollAmount += step;
        if(scrollAmount >= distance){
            window.clearInterval(slideTimer);
        }

        var scrollWidth = element.scrollWidth;
        var scrollLeft = element.scrollLeft;

        var outerWidth = $(element_name).outerWidth();
        var outerWidth = $("#" + element_name).outerWidth();
        if(scrollWidth - scrollLeft <= Math.round(outerWidth)) {
            $("." + arrow_name + "right").removeClass("dmiux_data-table__arrow_visible");
        }
        else if (scrollLeft == 0) {
            $("." + arrow_name + "left").removeClass("dmiux_data-table__arrow_visible");
            $("." + arrow_name + "right").addClass("dmiux_data-table__arrow_visible");
        }
        else {
            $("." + arrow_name + "left").addClass("dmiux_data-table__arrow_visible");
            $("." + arrow_name + "right").addClass("dmiux_data-table__arrow_visible");
        }
    }, speed);
}

const FileUploader = {
    file: null,
    file_size: null,
    chunk_size: 1024 * 1024 * 2,
    chunk_index: 0,
    chunk_start: 0,
    chunk_end: 0,
    chunk_errors: 0,
    chunks_total: 0,
    chunks_processed: 0,
    on_error: null,
    on_success: null,
    on_update: null,
    upload_url: null,
    upload_token: null,
    xhr: null,
    allowed_errors_per_chunk: 4,
    events: {
        UPDATE: 'on_update',
        ERROR: 'on_error',
        SUCCESS: 'on_success'
    },

    init(file_upload_url, upload_token) {
        this.upload_url = file_upload_url;
        this.upload_token = upload_token;
    },
    upload(file_element) {
        this.fireEvent(this.events.UPDATE, { progress: 0 });

        if (file_element == null || typeof file_element == 'undefined') {
            throw 'Invalid file element supplied.';
        }

        if (typeof file_element.files == 'undefined' || file_element.files.length === 0) {
            throw 'No file was selected.';
        }

        this.file = file_element.files[0];
        this.file_size = this.file.size;
        this.chunks_total = Math.ceil(this.file_size / this.chunk_size);
        this.chunks_processed = 0;
        this.chunk_start = 0;
        this.chunk_end = 0;
        this.chunk_index = 0;
        this.chunk_errors = 0;
        this.processChunk();
    },
    processChunk() {
      this.chunk_end = this.chunk_start + this.chunk_size;

      if (this.file_size - this.chunk_end < 0) {
        this.chunk_end = this.file_size;
      }

      this.chunk_index++;

      // Abstract the actual upload method so we can support parallel chunk uploads in the future
      this.uploadChunk(
          this.chunk_start,
          this.chunk_end,
          this.file.slice(this.chunk_start, this.chunk_end),
          this.chunk_index
      );
    },
    // Kick off the upload an individual chunk
    uploadChunk(start, end, chunk, index) {
      var formdata = new FormData();
      this.xhr = new XMLHttpRequest();

      this.xhr.onreadystatechange = this.handleStateChange;
      this.xhr.upload.addEventListener("progress", this.progressHandler, false);
      this.xhr.addEventListener("error", this.uploadErrorHandler, false);

      this.xhr.open('POST', this.upload_url, true);
    
      formdata.append('upload_token', this.upload_token);
      formdata.append('chunk_index', index);
      formdata.append('is_chunked', true);
      formdata.append('filename', this.file.name);
      formdata.append('start', start);
      formdata.append('end', end);
      formdata.append('file', chunk);

      // If we're on the last chunk, make sure we let the backend know
      if (this.chunk_end >= this.file_size) {
          formdata.append('is_last_chunk', true);
      }
    
      this.xhr.send(formdata);
    },
    abort() {
        if(this.xhr != null) {
            this.xhr.abort();
        }
    },
    fireEvent(eventName, eventData) {
        if (typeof this[eventName] === 'function') {
            this[eventName](eventData);
        }
    },
    // Call backs
    progressHandler(e) {
        var loadedThisTrip = e.loaded / e.total;

        FileUploader.fireEvent(FileUploader.events.UPDATE, {
            progress: Math.ceil(((FileUploader.chunks_processed + loadedThisTrip) / FileUploader.chunks_total) * 100)
        });
    },
    uploadErrorHandler(e, is_fatal = false) {
        if (is_fatal) {
            FileUploader.abort();
            FileUploader.fireEvent(FileUploader.events.ERROR);
            return;
        }

        if (FileUploader.chunk_index <= 1) {
            return;
        }

        FileUploader.chunk_errors++;

        if (FileUploader.chunk_errors >= FileUploader.allowed_errors_per_chunk) {
            FileUploader.fireEvent(FileUploader.events.ERROR);
            return;
        }

        // Retry...
        FileUploader.uploadChunk(
            FileUploader.chunk_start,
            FileUploader.chunk_end,
            FileUploader.file.slice(FileUploader.chunk_start, FileUploader.chunk_end),
            FileUploader.chunk_index
        );
    },
    handleStateChange(e) {
        if (e.target.readyState == 4) {

            if (e.target.status != 201) {
                FileUploader.uploadErrorHandler(e, e.target.status == 0);
                return;
            }

            FileUploader.chunks_processed += 1;
            FileUploader.chunk_errors = 0;

            FileUploader.fireEvent(FileUploader.events.UPDATE, {
                progress: Math.ceil((FileUploader.chunks_processed / FileUploader.chunks_total) * 100)
            });

            if (FileUploader.chunk_end < FileUploader.file_size) {
                FileUploader.chunk_start = FileUploader.chunk_end;
                FileUploader.processChunk();
                return;
            }

            FileUploader.fireEvent(FileUploader.events.SUCCESS, JSON.parse(e.target.responseText));
        }
    }
}