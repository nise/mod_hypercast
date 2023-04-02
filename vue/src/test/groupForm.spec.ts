import { afterAll, beforeEach, describe, expect, it, vi } from 'vitest';

import { mount, VueWrapper } from '@vue/test-utils';
import GroupForm from '../src/components/groupForm.vue';

// import axios from 'axios';
import Communication from '../scripts/communication';

describe('HelloWorld', () => {
    let wrapper: VueWrapper;

    beforeEach(() => {
        wrapper = mount(GroupForm);
    });

    afterAll(() => {
        wrapper.unmount();
    });

    it('form exists', () => {
        const form = wrapper.find('#group-form');
        expect(form.exists()).toBe(true);
    });

    it('name input exists', () => {
        const label = wrapper.find('#group-form_name_label');
        const input = wrapper.find('#group-form_name_input');
        expect(label.exists()).toBe(true);
        expect(label.text()).toBe('Gruppen Name');
        expect(input.exists()).toBe(true);
    });

    it('name input exists', () => {
        const label = wrapper.find('#group-form_desc_label');
        const input = wrapper.find('#group-form_desc_input');
        expect(label.exists()).toBe(true);
        expect(label.text()).toBe('Beschreibung');
        expect(input.exists()).toBe(true);
    });

    it('name input exists', () => {
        const button = wrapper.find('#group-form_submit-button');
        expect(button.exists()).toBe(true);
        expect(button.text()).toBe('Anlegen');
    });

    describe('when form is filled and button is clicked', () => {
        const exampleName = 'test name';
        const exampleDescription = 'test description';

        beforeEach(() => {
            // Fill Form
            wrapper.setData({
                data: {
                    name: exampleName,
                    desc: exampleDescription,
                },
            });

            // Trigger Submit on Form
            const form = wrapper.find('#group-form');
            // form.trigger('submit.prevent');

            // Mock Communication Class
            // TODO
        });

        it('a post request is send with the right data', () => {
            const expectedUrl = 'https://blabla:8081/api/groups';
            const expectedBody = {
                name: exampleName,
                desc: exampleDescription,
            };

            // TODO
            // expect(axios.post).toHaveBeenCalledTimes(1);
            // expect(axios.post).toBeCalledWith(expectedUrl, expectedBody);
        });
    });
});
