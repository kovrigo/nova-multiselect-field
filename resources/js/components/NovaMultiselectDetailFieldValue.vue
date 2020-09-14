<template>
  <div class="" v-if="!isEmpty">  
    <div class="multiselect-readonly">
      <div class="" v-if="groupRelations">
        <div v-for="(g, label) in values">
          <div class="group-label">{{ label }}</div>
          <ul class="grouped-list">
            <li v-for="s in g">
              {{ s.label }}
            </li>
          </ul>            
        </div>
      </div>
      <div class="" v-else>
        <ul>
          <li v-for="s in values">
            {{ s }}
          </li>
        </ul>          
      </div>
    </div>
  </div>
  <div v-else>—</div>
</template>

<script>
export default {
  props: ['field', 'values', 'from-relationship', 'group-relations'],

  computed: {

  	isEmpty: function () {
  		return _.isEmpty(this.values);
  	},

  },

};
</script>
