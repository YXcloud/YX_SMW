/**
 * Created by conghu on 2017/5/12.
 */

var app = angular.module('ngApp', ['Util.services']);

app.controller('inventoryController', function ($scope, $http, Util) {

        $scope.params = {
            page: 0,
            pagesize: 12
        };

        $.datetimepicker.setLocale('zh');

        $scope.vendorlist = [];
        $scope.stocklist = [];

        $scope.inv_mod = {};

        $scope.mtypelist = [
            {key: '1', value: '良品'},
            {key: '2', value: '不良品'}];

        $http.get('?/Receive/getVendorSelect/', {
            params: {}
        }).success(function (r) {
            $scope.vendorlist = r.ret_msg;
            //$scope.stocklist = r.ret_msg.stocklist;
        });

        $scope.vendorChange = function () {
            //供应商选择变化时 库区的下拉菜单联动
            $scope.stock_id = 0;
            $scope.selectChange();
            $http.get('?/Receive/getStockSelect/', {
                params: {
                    vendor_id: $scope.vendor_id
                }
            }).success(function (r) {
                $scope.stocklist = r.ret_msg;
                if ($scope.stocklist.length == 1) {
                    $scope.stock_id = $scope.stocklist[0].id;
                    $scope.selectChange();
                }
            });
        };

        $scope.stockChange = function () {
            $scope.selectChange();
        };

        $scope.selectChange = function () {
            //供应商和库区变化时 物料的下拉菜单联动
            $http.get('?/Receive/getGoodsList/', {
                params: {
                    vendor_id: $scope.vendor_id,
                    stock_id: $scope.stock_id
                }
            }).success(function (r) {
                $scope.goodslist = r.ret_msg;
                $("#goods_select").html("");
                if ($scope.goodslist.length > 0) {
                    $("#goods_select").select2({
                        placeholder: "请选择物料",
                        data: $scope.goodslist
                    }).val(0).trigger("change");
                }
            });
        };

        $scope.getList = function (e) {
            //开始查询
            $scope.init = false;
            fnGetList();
        };

        $scope.export = function (e) {
            //导出数据
            initparams();
            Util.loading();
            $http.get('?/Inventory/export/', {
                params: $scope.params
            }).success(function (r) {
                Util.loading(false);
                if (r.ret_code == 0) {
                    window.open(r.ret_msg);
                } else {
                    Util.Alert(r.ret_msg, true);
                }
            });
        };

        $scope.resetQuery = function (e) {
            //重置查询条件
            $scope.stocklist = [];
            $scope.goodslist = [];
            $scope.stock_id = 0;
            $scope.vendor_id = 0;
            $scope.goods_id = 0;
            $("#goods_select").html("");
            $scope.vendorChange();
        };

        $('#modal_modify_inventory').on('show.bs.modal', function (event) {
            $scope.inv_mod = {};
            $scope.inv = {};
            var btn = $(event.relatedTarget);
            $scope.goods_id = parseInt(btn.data('id'));
            if ($scope.goods_id > 0) {
                $http.get('?/Inventory/getList/', {
                    params: {
                        goods_id: $scope.goods_id
                    }
                }).success(function (r) {
                    $scope.inv = r.list[0];
                });
            }
        });

        $scope.mnumChange = function () {
            if ($scope.inv_mod.mtype == 1) {
                $scope.inv_mod.after = parseInt($scope.inv.quantity) + parseInt($scope.inv_mod.mnum);
            } else if ($scope.inv_mod.mtype == 2) {
                $scope.inv_mod.after = parseInt($scope.inv.abnormal) + parseInt($scope.inv_mod.mnum);
            }
        };

        $scope.modify = function () {
            $scope.mnumChange();
            if ($scope.inv_mod.mtype == 1) {
                $scope.inv_mod.before = parseInt($scope.inv.quantity);
            } else if ($scope.inv_mod.mtype == 2) {
                $scope.inv_mod.before = parseInt($scope.inv.abnormal);
            }
            if ($scope.inv_mod.mtype == null || !$scope.inv_mod.mtype > 0) {
                Util.alert('请选择调整类型', true);
                return;
            }
            if (!$scope.inv_mod.mnum > 0 || isNaN($scope.inv_mod.mnum)) {
                Util.alert('调整数量不正确', true);
                return;
            }
            if ($scope.inv_mod.remark == null || !$scope.inv_mod.remark.length > 0) {
                Util.alert('请填写调整说明', true);
                return;
            }
            if ($scope.goods_id > 0) {
                $scope.inv_mod.goods_id = $scope.goods_id;
                Util.loading();
                $.post('?/Inventory/modify/', $scope.inv_mod, function (r) {
                    Util.loading(false);
                    if (r.ret_code === 0) {
                        $('#modal_modify_inventory').modal('hide');
                        Util.alert('保存成功');
                        fnGetList();
                    } else {
                        Util.alert('操作失败 ' + r.ret_msg, true);
                    }
                });
            } else {
                Util.alert('操作失败 物料信息不正确', true);
            }

        }

        function initparams() {
            $scope.params.stock_id = $scope.stock_id;
            $scope.params.vendor_id = $scope.vendor_id;
            $scope.params.goods_id = $("#goods_select").val();
        }

        function fnGetList() {
            initparams();
            Util.loading();
            $http.get('?/Inventory/getList/', {
                params: $scope.params
            }).success(function (r) {
                Util.loading(false);
                var sjson = r.list;
                $scope.inventoryList = sjson;
                $scope.listcount = r.total;
                if (!$scope.init) {
                    $scope.init = true;
                    fnInitPager();
                }
            });
        }

        /**
         * 初始化分页
         * @returns {x}
         */
        function fnInitPager() {
            var page = 1;
            if ($scope.listcount > 0) {
                page = Math.ceil($scope.listcount / $scope.params.pagesize);
            }
            Util.initPaginator(page, function (page) {
                $('body').animate({scrollTop: '0'}, 200);
                $scope.params.page = page - 1;
                fnGetList();
            });
        }

        fnGetList();

        $scope.vendorChange();

    }
)
;
