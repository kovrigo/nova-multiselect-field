<template>
  <div class="relative rounded-lg bg-white shadow border border-60" v-if="!isEmpty">
    <div class="overflow-hidden rounded-b-lg rounded-t-lg">
    	<!-- Plain list -->
    	<div v-if="!groupRelations">
			<div class="border-b border-50 cursor-text font-mono text-sm py-2 px-4" v-for="(value, i) of values" :key="i">
				{{ value }}
			</div>
		</div>
      	<!-- Grouped list -->
      	<div v-else>
			<div class="border-b border-50 cursor-text font-mono text-sm py-2 px-4" 
				v-for="(options, group) in values" :key="group">
				{{ group }}
				<div class="cursor-text font-mono text-xs py-1 px-4" :class="i == 0 ? 'pt-2' : ''"
					v-for="(option, i) of options" :key="i">
					{{ option.label }}
				</div>
			</div>
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
