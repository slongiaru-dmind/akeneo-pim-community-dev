import {ReactNode} from 'react';
import requireContext from '../dependencies/require-context';

type ModuleConfig = {
    providers: {
        [key: string]: {
            module: string;
            order?: number;
        };
    };
};

export interface PermissionFormProvider<T> {
    key: string;
    label: string;
    renderForm: (onChange: (state: T) => void, initialState: T | undefined) => ReactNode;
    renderSummary: (state: T) => ReactNode;
    save: (userGroup: string, state: T) => Promise<void>;
}

let _config: ModuleConfig = {
    providers: {},
};

const PermissionFormRegistry = {
    setModuleConfig: (config: ModuleConfig) => {
        _config = config;
    },
    all: async (): Promise<PermissionFormProvider<any>[]> => {
        const providers = _config.providers;

        const modules = Object.keys(providers)
            .sort((a, b) => {
                return (providers[a].order ?? 0) - (providers[b].order ?? 0);
            })
            .map(key => providers[key].module);

        return await Promise.all(
            modules.map(async (module): Promise<any> => {
                return (await requireContext(module)).default;
            })
        );
    },
    count: (): number => {
        const providers = _config.providers || [];

        return Object.keys(providers).length;
    },
};

export default PermissionFormRegistry;