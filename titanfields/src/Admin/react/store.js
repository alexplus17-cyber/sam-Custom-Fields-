import { createReduxStore, register } from '@wordpress/data';

const DEFAULT_STATE = {
    groupDetails: {
        title: '',
        key: '',
        isActive: true,
    },
    fields: [],
    locationRules: {
        relation: 'OR',
        groups: []
    }
};

const actions = {
    setGroupTitle(title) {
        return {
            type: 'SET_GROUP_TITLE',
            title,
        };
    },
    addField(field) {
        return {
            type: 'ADD_FIELD',
            field,
        };
    },
    updateRuleGroups(groups) {
        return {
            type: 'UPDATE_RULE_GROUPS',
            groups,
        };
    }
};

const reducer = (state = DEFAULT_STATE, action) => {
    switch (action.type) {
        case 'SET_GROUP_TITLE':
            return {
                ...state,
                groupDetails: {
                    ...state.groupDetails,
                    title: action.title
                }
            };
        case 'ADD_FIELD':
            return {
                ...state,
                fields: [...state.fields, action.field]
            };
        case 'UPDATE_RULE_GROUPS':
            return {
                ...state,
                locationRules: {
                    ...state.locationRules,
                    groups: action.groups
                }
            };
        default:
            return state;
    }
};

const selectors = {
    getGroupTitle(state) {
        return state.groupDetails.title;
    },
    getFields(state) {
        return state.fields;
    },
    getLocationRules(state) {
        return state.locationRules.groups;
    }
};

const store = createReduxStore('titanfields/groups', {
    reducer,
    actions,
    selectors,
});

register(store);
