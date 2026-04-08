import React from 'react';

export default function StatCard({ title, value, subtitle, icon, color = 'primary' }) {
    return (
        <div className="col-12 md:col-6 xl:col-3">
            <div className="card mb-0">
                <div className="flex justify-content-between align-items-start gap-3">
                    <div>
                        <span className="block text-500 font-medium mb-2">{title}</span>
                        <div className="text-900 font-bold text-3xl">{value}</div>
                        {subtitle ? <div className="text-600 mt-2">{subtitle}</div> : null}
                    </div>
                    <div className={`border-circle bg-${color}-100 flex align-items-center justify-content-center`} style={{ width: 48, height: 48 }}>
                        <i className={`pi pi-${icon} text-${color}-500 text-xl`} />
                    </div>
                </div>
            </div>
        </div>
    );
}
