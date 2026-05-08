import React, { useState } from 'react';
import { useForm, Head } from '@inertiajs/react';

export default function Dashboard({ order, reasons }) {
    const { data, setData, post, processing, errors } = useForm({
        items: [], // { order_item_id, return_reason_id, quantity, condition }
        customer_notes: '',
        evidences: [],
    });

    const [selectedItems, setSelectedItems] = useState({});

    const handleItemToggle = (item) => {
        const isSelected = !!selectedItems[item.order_item_id];
        
        if (isSelected) {
            const newSelected = { ...selectedItems };
            delete newSelected[item.order_item_id];
            setSelectedItems(newSelected);
            
            setData('items', data.items.filter(i => i.order_item_id !== item.order_item_id));
        } else {
            setSelectedItems({
                ...selectedItems,
                [item.order_item_id]: true
            });
            
            setData('items', [
                ...data.items, 
                {
                    order_item_id: item.order_item_id,
                    return_reason_id: '',
                    quantity: 1,
                    condition: 'sealed' // default
                }
            ]);
        }
    };

    const updateItemData = (order_item_id, field, value) => {
        setData('items', data.items.map(i => {
            if (i.order_item_id === order_item_id) {
                return { ...i, [field]: value };
            }
            return i;
        }));
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('returns.tickets.store'));
    };

    return (
        <div className="min-h-screen bg-gray-50 py-10">
            <Head title="Dashboard de Devoluciones - Tai Loy" />

            <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="bg-white shadow overflow-hidden sm:rounded-lg border-t-4 border-tailoy-blue mb-8">
                    <div className="px-4 py-5 sm:px-6 flex justify-between items-center">
                        <div>
                            <h3 className="text-lg leading-6 font-bold text-gray-900">Pedido #{order.order_number}</h3>
                            <p className="mt-1 max-w-2xl text-sm text-gray-500">
                                Fecha de compra: {new Date(order.order_date).toLocaleDateString()}
                            </p>
                        </div>
                        <div className="bg-tailoy-yellow text-tailoy-blue px-4 py-1 rounded-full font-bold text-sm">
                            Elegible para Devolución
                        </div>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-8" encType="multipart/form-data">
                    <div className="bg-white shadow overflow-hidden sm:rounded-lg">
                        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
                            <h3 className="text-lg leading-6 font-bold text-gray-900">1. Selecciona los productos</h3>
                            <p className="mt-1 text-sm text-gray-500">Marca los productos que deseas devolver o reportar por garantía.</p>
                        </div>
                        
                        <ul className="divide-y divide-gray-200">
                            {order.order_items.map((item) => {
                                const isSelected = !!selectedItems[item.order_item_id];
                                const itemData = data.items.find(i => i.order_item_id === item.order_item_id);

                                return (
                                    <li key={item.order_item_id} className="p-4 sm:px-6">
                                        <div className="flex items-center mb-4">
                                            <input
                                                id={`item-${item.order_item_id}`}
                                                type="checkbox"
                                                checked={isSelected}
                                                onChange={() => handleItemToggle(item)}
                                                className="h-5 w-5 text-tailoy-blue focus:ring-tailoy-blue border-gray-300 rounded"
                                            />
                                            <label htmlFor={`item-${item.order_item_id}`} className="ml-3 flex flex-col cursor-pointer">
                                                <span className="text-sm font-bold text-gray-900">{item.product_name}</span>
                                                <span className="text-xs text-gray-500">Cod: {item.product_code} | Precio: S/ {item.unit_price} | Cantidad comprada: {item.quantity}</span>
                                            </label>
                                        </div>

                                        {isSelected && itemData && (
                                            <div className="ml-8 mt-4 grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-6 bg-gray-50 p-4 rounded-md border border-gray-100">
                                                <div className="sm:col-span-2">
                                                    <label className="block text-xs font-semibold text-gray-700">Cantidad a devolver</label>
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        max={item.quantity}
                                                        value={itemData.quantity}
                                                        onChange={(e) => updateItemData(item.order_item_id, 'quantity', parseInt(e.target.value))}
                                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-tailoy-blue focus:border-tailoy-blue sm:text-sm"
                                                    />
                                                </div>

                                                <div className="sm:col-span-2">
                                                    <label className="block text-xs font-semibold text-gray-700">Motivo</label>
                                                    <select
                                                        value={itemData.return_reason_id}
                                                        onChange={(e) => updateItemData(item.order_item_id, 'return_reason_id', e.target.value)}
                                                        className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-tailoy-blue focus:border-tailoy-blue sm:text-sm rounded-md"
                                                        required
                                                    >
                                                        <option value="">Seleccione...</option>
                                                        {reasons.map(reason => (
                                                            <option key={reason.reason_id} value={reason.reason_id}>{reason.description}</option>
                                                        ))}
                                                    </select>
                                                </div>

                                                <div className="sm:col-span-2">
                                                    <label className="block text-xs font-semibold text-gray-700">Estado del producto</label>
                                                    <select
                                                        value={itemData.condition}
                                                        onChange={(e) => updateItemData(item.order_item_id, 'condition', e.target.value)}
                                                        className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-tailoy-blue focus:border-tailoy-blue sm:text-sm rounded-md"
                                                    >
                                                        <option value="sealed">Sellado / Intacto</option>
                                                        <option value="opened">Abierto / Usado</option>
                                                        <option value="damaged">Dañado de fábrica</option>
                                                    </select>
                                                </div>
                                            </div>
                                        )}
                                    </li>
                                );
                            })}
                        </ul>
                        {errors.items && <p className="p-4 text-sm text-red-600 font-medium">{errors.items}</p>}
                    </div>

                    {data.items.length > 0 && (
                        <>
                            <div className="bg-white shadow overflow-hidden sm:rounded-lg px-4 py-5 sm:px-6">
                                <h3 className="text-lg leading-6 font-bold text-gray-900 mb-4">2. Detalles adicionales y Evidencia</h3>
                                
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700">Comentarios (Opcional)</label>
                                        <p className="text-xs text-gray-500 mb-2">Cuéntanos más detalles sobre el problema.</p>
                                        <textarea
                                            rows={3}
                                            className="shadow-sm focus:ring-tailoy-blue focus:border-tailoy-blue block w-full sm:text-sm border-gray-300 rounded-md"
                                            value={data.customer_notes}
                                            onChange={e => setData('customer_notes', e.target.value)}
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-700">Fotos o Documentos de Evidencia</label>
                                        <p className="text-xs text-gray-500 mb-2">Sube imágenes del producto dañado o comprobantes. (Max 5MB por archivo, JPG/PNG/PDF)</p>
                                        
                                        <input
                                            type="file"
                                            multiple
                                            accept=".jpg,.jpeg,.png,.pdf"
                                            onChange={e => setData('evidences', Array.from(e.target.files))}
                                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-tailoy-blue file:text-white hover:file:bg-blue-800"
                                        />
                                        {errors.evidences && <p className="mt-2 text-sm text-red-600">{errors.evidences}</p>}
                                        {Object.keys(errors).map(key => {
                                            if (key.startsWith('evidences.')) {
                                                return <p key={key} className="mt-1 text-sm text-red-600">{errors[key]}</p>;
                                            }
                                            return null;
                                        })}
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-base font-bold rounded-md text-tailoy-blue bg-tailoy-yellow hover:bg-yellow-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-tailoy-yellow disabled:opacity-50"
                                >
                                    {processing ? 'Enviando...' : 'Enviar Solicitud'}
                                </button>
                            </div>
                        </>
                    )}
                </form>
            </div>
        </div>
    );
}
