<template>
  <panel-item :field="field">
    <template slot="value">
      <nova-multiselect-detail-field-value v-if="isMultiselect" 
        :field="field" 
        :values="values" 
        :from-relationship="field.fromRelationship"
        :group-relations="field.groupRelations"
      />
      <div v-else>{{ (value && value.label) || '—' }}</div>
    </template>
  </panel-item>
</template>

<script>
import HandlesFieldValue from '../mixins/HandlesFieldValue';

export default {
  mixins: [HandlesFieldValue],

  props: ['resource', 'resourceName', 'resourceId', 'field'],

  data: () => ({
    relationshipValues: null,
  }),

  mounted() {
      if (this.field.fromRelationship) {
        this.loadInitialValueFromRelationship();
        return;
      }
  },

  methods: {

    loadInitialValueFromRelationship() {
        let baseUrl = '/nova-vendor/nova-multiselect/';
        let queryParams = '?multiselect-resource=' + _.toString(this.resourceName) + '&multiselect-resource-id=' + _.toString(this.resourceId) + '&multiselect-via-resource=' + _.toString(this.viaResource) + '&multiselect-via-resource-id=' + _.toString(this.viaResourceId);
        Nova.request(baseUrl + this.resourceName + '/' + this.resourceId + '/attachable/' + this.field.attribute + queryParams)
          .then((data) => {
            if (this.field.groupRelations) {
              let relationshipValues = _.map(data.data.selected, function (value) {
                return _.clone(_.find(data.data.available, ['value', value]));
              });
              this.relationshipValues = _.groupBy(relationshipValues, function (value) {
                return value.group;
              });
            } else {
              this.relationshipValues = _.map(data.data.selected, function (value) {
                return _.clone(_.find(data.data.available, ['value', value])).label;
              });
            }
          });
      },

  },

  computed: {
    values() {
      if (this.field.fromRelationship) {
        return this.relationshipValues;
      }
      const valuesArray = this.getInitialFieldValuesArray();
      if (!valuesArray || !valuesArray.length) return;

      return valuesArray
        .map(this.getValueFromOptions)
        .filter(Boolean)
        .map(val => `${this.isOptionGroups ? `[${val.group}] ` : ''}${val.label}`);
    },

    value() {
      return this.getValueFromOptions(this.field.value);
    },
  },
};
</script>
