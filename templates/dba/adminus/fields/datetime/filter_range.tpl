                    <table class="datatimeField">
                    <tr>
                    <td>{lang value="FROM"}:</td><td nowrap="nowrap"><input type="text" name="filter[{$filterName}][0]" id="filter[{$filterName}][0]" value="{$value[0]}" size="10" style="vertical-align: top">
<input type="reset" value=" ... " class="button" style="vertical-align:top;" id="{$attributes.name}_cal_f" name="{$attributes.name}_cal_f"> 
                    <script type="text/javascript">
                        {literal}Calendar.setup({{/literal}
                            inputField     :    "filter[{$filterName}][0]",
                            ifFormat       :    "%Y-%m-%d",
                            showsTime      :    false,
                            button         :    "{$attributes.name}_cal_f",
                            step           :    1
                            {literal}});{/literal}
                    </script>
                    </td>
                    </tr>
                    <tr>
                    <td>{lang value="TO"}:</td><td><input type="text" name="filter[{$filterName}][1]" id="filter[{$filterName}][1]" value="{$value[1]}" size="10" style="vertical-align: top">
                    <input type="reset" value=" ... " class="button" style="vertical-align:top;" id="{$attributes.name}_cal_t" name="{$attributes.name}_cal_t"> 
                    <script type="text/javascript">
                    
                    {literal}Calendar.setup({{/literal}
                            inputField     :    "filter[{$filterName}][1]",
                            ifFormat       :    "%Y-%m-%d",
                            showsTime      :    false,
                            button         :    "{$attributes.name}_cal_t",
                            step           :    1
                            {literal}});{/literal}
                    </script>
                    </td>
                    </tr>
                    </table>