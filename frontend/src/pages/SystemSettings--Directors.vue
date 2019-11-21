<template>
<div class="card border-secondary mb-3">
    <div class="card-body">
        <p>
            Login URL for characters with director role:
            <a :href="loginUrlDirector">{{ loginUrlDirector }}</a>
            <br>
            This is used to get the <a href="#Tracking">Member Tracking</a> data from ESI.
        </p>
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Character</th>
                    <th colspan="2">Corporation</th>
                    <th colspan="2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="director in directors">
                    <td>
                        <a :href="'https://evewho.com/character/' + director.value['character_id']"
                           title="Eve Who" target="_blank" rel="noopener noreferrer">
                            {{ director.value['character_name'] }}
                        </a>
                    </td>
                    <td>{{ director.value['corporation_id'] }}</td>
                    <td>
                        [{{ director.value['corporation_ticker'] }}]
                        {{ director.value['corporation_name'] }}
                    </td>
                    <td>
                        <button type="button" class="btn btn-info"
                                v-on:click="validateDirector(director.name)">
                            <span class="fas fa-check"></span>
                            validate
                        </button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger"
                                v-on:click="removeDirector(director.name)">
                            <span class="fas fa-minus-circle"></span>
                            remove
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
</template>

<script>
import { SettingsApi } from 'neucore-js-client';

export default {
    props: {
        settings: Object,
    },

    data () {
        return {
            loginUrlDirector: null,
            directors: [],
        }
    },

    mounted () {
        readSettings(this);

        // login URL for director chars
        let port = '';
        if (location.port !== "" && location.port !== 80 && location.port !== 443) {
            port = ':' + location.port;
        }
        this.loginUrlDirector = location.protocol + "//" + location.hostname + port + "/login-director"
    },

    watch: {
        settings () {
            readSettings(this);
        },
    },

    methods: {
        validateDirector: function(name) {
            const vm = this;
            new SettingsApi().validateDirector(name, function(error, data) {
                if (error) { // 403 usually
                    return;
                }
                vm.$root.message(
                    data ? 'The Token is valid and character has the director role.' : 'Validation failed.',
                    data ? 'info' : 'warning'
                );
                vm.$root.$emit('settingsChange');
            });
        },

        removeDirector (name) {
            this.$emit('changeSetting', name, '');
        },
    },
}

function readSettings(vm) {
    if (! vm.settings.hasOwnProperty('account_deactivation_delay')) {
        return; // wait for settings
    }

    vm.directors = [];

    for (const [name, value] of Object.entries(vm.settings)) {
        if (name.indexOf('director_char_') === -1) {
            continue
        }
        try {
            vm.directors.push({
                'name': name,
                'value': JSON.parse(value)
            });
        } catch(err) {}
    }
}
</script>