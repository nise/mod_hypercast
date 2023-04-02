import ajax from 'core/ajax';

export default {
    fullName: '',
    setPluginName(name): void {
        this.fullName = name;
    },
    webservice(method, param = {}): Promise<any> {
        if (typeof this.fullName !== 'string') {
            throw new Error('No plugin name given at communication class.');
        }
        return new Promise((resolve, reject) => {
            ajax.call([
                {
                    methodname: `${this.fullName}_${method}`,
                    args: param ? param : {},
                    timeout: 3000,
                    done: resolve,
                    fail: reject,
                },
            ]);
        });
    }
}
