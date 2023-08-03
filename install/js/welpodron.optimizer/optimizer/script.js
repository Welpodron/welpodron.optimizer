"use strict";
(() => {
    if (!window.welpodron) {
        window.welpodron = {};
    }
    if (window.welpodron.optimizer) {
        return;
    }
    //! Подразумевается что единственный instance поэтому Singleton
    class Optimizer {
        sessid = "";
        isLoading = false;
        constructor({ sessid, config = {} }) {
            if (Optimizer._instance) {
                return Optimizer._instance;
            }
            this.sessid = sessid.trim();
            Optimizer._instance = this;
        }
        optimize = async (path) => {
            if (!path) {
                return;
            }
            if (this.isLoading) {
                return;
            }
            this.isLoading = true;
            try {
                const data = new FormData();
                data.set("sessid", this.sessid);
                data.set("path", path);
                const response = await fetch("/bitrix/services/main/ajax.php?action=welpodron%3Aoptimizer.Receiver.load", {
                    method: "POST",
                    body: data,
                });
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                const bitrixResponse = await response.json();
                if (bitrixResponse.status === "error") {
                    return console.error(bitrixResponse);
                }
                const { data: responseData } = bitrixResponse;
                if (responseData.FILE_EXT === "css") {
                    if (window.csso) {
                        const minified = window.csso.minify(responseData.FILE_CONTENT, {
                            sourceMap: true,
                            filename: responseData.FILE_DIRECTORY + responseData.FILE_NAME + ".css",
                            comments: false,
                        });
                        const data = new FormData();
                        data.set("sessid", this.sessid);
                        data.set("file_path", responseData.FILE_DIRECTORY + responseData.FILE_NAME + ".min.css");
                        data.set("file_content", minified.css +
                            `/*# sourceMappingURL=${responseData.FILE_DIRECTORY +
                                responseData.FILE_NAME +
                                ".min.css.map"} */`);
                        data.set("map_path", responseData.FILE_DIRECTORY +
                            responseData.FILE_NAME +
                            ".min.css.map");
                        data.set("map_content", minified.map.toString());
                        const response = await fetch("/bitrix/services/main/ajax.php?action=welpodron%3Aoptimizer.Receiver.save", {
                            method: "POST",
                            body: data,
                        });
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        const bitrixResponse = await response.json();
                        if (bitrixResponse.status === "error") {
                            return console.error(bitrixResponse);
                        }
                        if (bitrixResponse.status === "success") {
                            window.location.reload();
                        }
                    }
                    else {
                        throw new Error("csso не найден");
                    }
                }
                if (responseData.FILE_EXT === "js") {
                    if (window.UglifyJS) {
                        const minified = window.UglifyJS.minify({
                            [`${responseData.FILE_NAME}.js`]: responseData.FILE_CONTENT,
                        }, {
                            sourceMap: {
                                filename: `${responseData.FILE_NAME}.min.js`,
                                url: `${responseData.FILE_NAME}.min.js.map`,
                            },
                        });
                        const data = new FormData();
                        data.set("sessid", this.sessid);
                        data.set("file_path", responseData.FILE_DIRECTORY + responseData.FILE_NAME + ".min.js");
                        data.set("file_content", minified.code);
                        data.set("map_path", responseData.FILE_DIRECTORY +
                            responseData.FILE_NAME +
                            ".min.js.map");
                        data.set("map_content", minified.map);
                        const response = await fetch("/bitrix/services/main/ajax.php?action=welpodron%3Aoptimizer.Receiver.save", {
                            method: "POST",
                            body: data,
                        });
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        const bitrixResponse = await response.json();
                        if (bitrixResponse.status === "error") {
                            return console.error(bitrixResponse);
                        }
                        if (bitrixResponse.status === "success") {
                            window.location.reload();
                        }
                    }
                    else {
                        throw new Error("UglifyJS не найден");
                    }
                }
            }
            catch (error) {
                console.error(error);
            }
            finally {
                this.isLoading = false;
            }
        };
    }
    window.welpodron.optimizer = Optimizer;
})();
