import ChartJS from 'chart.js'
import { useEffect, createRef } from 'react'

const Chart = (props) => {
    const setup = props.setup
    const canvas = createRef()
    useEffect(() => {
        const ctx = canvas.current.getContext('2d')
        console.log('ctx!', ctx)
        const chart = new ChartJS(ctx, {
            type: setup.type,
            data: setup.data,
            options: setup.options
        })
    }, [])
    return (
        <div>
            <canvas width={100} height={100 * setup.aspectRatio}  ref={canvas}></canvas>
        </div>
    )
}
export default Chart