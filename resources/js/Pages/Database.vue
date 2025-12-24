<template>
  <Content>
    <div class="row">
      <div class="col-md-6">
        <Card varient="gray" body-class="p-0">
          <template #title>
            Classes <i v-show="loadingTable" class="fa fa-spinner fa-spin"></i>
          </template>
          <template #title_right>
            <a :href="route('db.download')">Download</a>
          </template>
          
          <div class="m-2">
            <form @submit.prevent="submit">
              <div class="form-group">
                <label for="db_file">Database File *</label>
                <input id="db_file" class="form-control" :class="{ 'is-invalid': form.errors.file }" type="file" @input="inputfile"
                accept=""/>
                <progress v-if="form.progress" :value="form.progress.percentage" max="100">
                  {{ form.progress.percentage }}%
                </progress>
                <span
                  id="file-error"
                  class="error invalid-feedback"
                  >{{ form.errors.file }}</span>
              </div>
              <Input
                optional
                v-model="form.file_name"
                field="file_name"
                label="Backup name of previous database"
                :form="form"
                placeholder="full backup / after change"
              />
              <div class="form-group">
                <Button>
                  <i class="fa" :class="{'fa-spinner fa-spin': form.processing, 'fa-save': !form.processing}"></i>
                  Save
                </Button>
              </div>
              
              <div class="m-2">
                <div class="px-2 py-1" v-for="(file, index) in files"
                style="border: 1px solid #cbcbcb; border-radius: 4px; margin-bottom: 8px;">
                  <div class="d-flex align-items-center">
                    <div class="flex-1 d-flex align-items-center">
                      <div style="height: 70px; width: 70px; text-align: center;
                      justify-content: center; border-radius: 50%; background:
                      #c8fffc; color: #15fff2" class="d-flex align-items-center">
                        <i class="fa fa-database" style="font-size: 40px;"></i>
                      </div>
                      <div class="ml-2">
                        <div>{{ file.name.substring(0, 26) }}</div>
                        <div>{{ file.date }} {{ file.time }}</div>
                        <div>{{ file.size }} KB</div>
                      </div>
                    </div>
                    <div style="width: 50px; text-align: right;">
                      <Dropdown
                        stay
                        header="Actions"
                        id="hi"
                      >
                        <Button
                          btnDropdown
                          type="button"
                          @click="deleteFile(file.name)"
                        >
                          <i class="fa fa-trash"></i> Delete
                        </Button>
                        <Button
                          btnDropdown
                          type="button"
                          @click="restoreFile(file.name)"
                        >
                          <i class="fa fa-undo"></i> Restore
                        </Button>
                      </Dropdown>
                    </div>
                  </div>
                </div>
              </div>
              
              <div>
                <pre>
                  
                </pre>
              </div>
            </form>
          </div>
        </Card>
      </div>
    </div>
  </Content>
  
</template>

<script>
import {
  AdminLayout,
  Modal,
  Card,
  Input,
  DeleteConfirm,
  Spinner,
  Dropdown,
  Pagination,
  Filterth,
  Button,
  Content,
  useValidateForm,
} from "@/Components";
import toast from "@/Store/toast.js";
import _ from "lodash";
import { Inertia } from "@inertiajs/inertia";
import {useForm} from "@inertiajs/inertia-vue3";
import { reactive, ref } from "vue";

export default {
  name: "Classes",
  layout: AdminLayout,
  components: {
    Spinner,
    Modal,
    Input,
    Content,
    DeleteConfirm,
    Card,
    Button,
    Dropdown,
    Filterth,
    Pagination,
  },
  props: {
    url: Text,
    files: Object,
  },
  data() {
    return {
      form: useForm({
        file: "",
        file_name: "",
      }),
      loadingTable: false,
    };
  },
  
  methods: {
    inputfile(e){
      const file = e.target.files[0]
      this.form.file = file
    },
    submit() {
      this.form.clearErrors();
      try{
        this.form.post(route('db.upload'), {
          preserveScroll: true,
          onSuccess: () => {
            this.form.reset();
          },
          onError: error => {
            console.log(error)
          }
        });
      }catch(error){
        console.log(error)
      }
    },
    deleteFile(name){
      //alert(name)
      Inertia.delete(route("db.delete", name), {
        replace: true,
        onBefore: () => {
          this.loadingTable = true;
        },
        onFinish: (finish) => {
          this.loadingTable = false;
        }
      });
    },
    restoreFile(name){
      //alert(name)
      Inertia.post(route("db.restore", name), {
        replace: true,
        onBefore: () => {
          this.loadingTable = true;
        },
        onFinish: (finish) => {
          this.loadingTable = false;
        }
      });
    },
  }
}
</script>


<style scoped>
  
  .items_container {
    --item: #e6e6e6;
    border: 1px solid var(--item);
    border-radius: 4px;
    margin-bottom: 5px;
  }
  .item_header {
    padding: 8px 10px;
    background: var(--item);
  }
  .item_header span {
    color: #000;
    font-weight: bold;
    font-size: 1.35rem;
  }
  .item {
    padding: 8px;
    overflow: visible;
  }
</style>
